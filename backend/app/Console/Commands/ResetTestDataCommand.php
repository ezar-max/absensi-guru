<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetTestDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-test-data {--seed : Re-seed sample data after wipe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengosongkan semua data uji coba (absensi, jadwal, guru, kelas, sekretaris, dll) dan mempertahankan akun Administrator';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pengosongan data pengujian...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Truncate tabel operasional & transaksi
        $tablesToTruncate = [
            'attendances',
            'audit_logs',
            'schedules',
            'class_user',
            'holidays',
            'personal_access_tokens',
            'teachers',
            'subjects',
            'classes',
            'periods',
        ];

        foreach ($tablesToTruncate as $table) {
            DB::table($table)->truncate();
            $this->line("  ✓ Tabel [{$table}] telah dikosongkan.");
        }

        // 2. Bersihkan user sekretaris, pertahankan / perbarui akun admin
        DB::table('users')->where('role', '!=', 'admin')->delete();
        $this->line('  ✓ Akun-akun sekretaris uji coba telah dihapus.');

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin TU / Kurikulum',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $this->line('  ✓ Akun Administrator (admin@gmail.com) dipastikan aktif.');

        // 3. Pastikan pengaturan default tersimpan
        Setting::set('late_tolerance_min', 10);
        Setting::set('absent_threshold_pct', 50);
        Setting::set('edit_lock_min', 15);
        $this->line('  ✓ Parameter kebijakan absensi diset ke nilai default.');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        if ($this->option('seed')) {
            $this->info('Menjalankan seeder ulang...');
            $this->call('db:seed');
        }

        $this->info('Pengosongan data selesai. Database bersih dan siap untuk pengujian mandiri.');
        return Command::SUCCESS;
    }
}
