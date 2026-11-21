<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Settings
        Setting::set('late_tolerance_min', 10);
        Setting::set('absent_threshold_pct', 50);
        Setting::set('edit_lock_min', 15);

        // 2. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin TU / Kurikulum',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 3. Classes (3 kelas)
        $class1 = SchoolClass::firstOrCreate(
            ['name' => 'X IPA 1', 'academic_year' => '2026/2027'],
            ['major' => 'IPA']
        );
        $class2 = SchoolClass::firstOrCreate(
            ['name' => 'X IPA 2', 'academic_year' => '2026/2027'],
            ['major' => 'IPA']
        );
        $class3 = SchoolClass::firstOrCreate(
            ['name' => 'XI IPA 1', 'academic_year' => '2026/2027'],
            ['major' => 'IPA']
        );

        // 4. Secretaries (2 sekretaris di kelas X IPA 1 untuk uji multi-sekretaris)
        $sec1 = User::firstOrCreate(
            ['email' => 'sekretaris1@sekolah.test'],
            [
                'name' => 'Sekretaris Utama (Siti Aisyah)',
                'nis' => '1022001',
                'password' => Hash::make('password'),
                'role' => 'secretary',
                'is_active' => true,
            ]
        );
        $sec2 = User::firstOrCreate(
            ['email' => 'sekretaris2@sekolah.test'],
            [
                'name' => 'Sekretaris Cadangan (Bambang Pamungkas)',
                'nis' => '1022002',
                'password' => Hash::make('password'),
                'role' => 'secretary',
                'is_active' => true,
            ]
        );

        // Kaitkan kedua sekretaris ke X IPA 1
        $class1->secretaries()->syncWithoutDetaching([$sec1->id, $sec2->id]);

        // Sekretaris kelas X IPA 2
        $sec3 = User::firstOrCreate(
            ['email' => 'sekretaris3@sekolah.test'],
            [
                'name' => 'Sekretaris X IPA 2 (Rina Melati)',
                'nis' => '1022003',
                'password' => Hash::make('password'),
                'role' => 'secretary',
                'is_active' => true,
            ]
        );
        $class2->secretaries()->syncWithoutDetaching([$sec3->id]);

        // 5. Subjects (5 mapel)
        $subMath = Subject::firstOrCreate(['name' => 'Matematika']);
        $subIndo = Subject::firstOrCreate(['name' => 'Bahasa Indonesia']);
        $subEng  = Subject::firstOrCreate(['name' => 'Bahasa Inggris']);
        $subPhys = Subject::firstOrCreate(['name' => 'Fisika']);
        $subBio  = Subject::firstOrCreate(['name' => 'Biologi']);
        $subInfo = Subject::firstOrCreate(['name' => 'Informatika']);

        // 6. Teachers (6 guru)
        $t1 = Teacher::firstOrCreate(['name' => 'Pak Ahmad S.Pd', 'nip' => '198001012005011001', 'is_active' => true]);
        $t2 = Teacher::firstOrCreate(['name' => 'Bu Siti Nurhaliza M.Pd', 'nip' => '198202022006022002', 'is_active' => true]);
        $t3 = Teacher::firstOrCreate(['name' => 'Pak Budi Santoso S.Si', 'nip' => '197903032003011003', 'is_active' => true]);
        $t4 = Teacher::firstOrCreate(['name' => 'Bu Dewi Lestari S.Pd', 'nip' => '198504042008022004', 'is_active' => true]);
        $t5 = Teacher::firstOrCreate(['name' => 'Pak Eko Prasetyo S.Kom', 'nip' => '198705052010011005', 'is_active' => true]);
        $t6 = Teacher::firstOrCreate(['name' => 'Bu Rina Wati S.Pd', 'nip' => '199006062015022006', 'is_active' => true]);

        $t1->subjects()->syncWithoutDetaching([$subMath->id]);
        $t2->subjects()->syncWithoutDetaching([$subIndo->id]);
        $t3->subjects()->syncWithoutDetaching([$subPhys->id]);
        $t4->subjects()->syncWithoutDetaching([$subEng->id]);
        $t5->subjects()->syncWithoutDetaching([$subInfo->id]);
        $t6->subjects()->syncWithoutDetaching([$subBio->id]);

        // 7. Periods (6 jam pelajaran tanpa tumpang tindih)
        $p1 = Period::firstOrCreate(['label' => 'Jam ke-1'], ['start_time' => '07:00:00', 'end_time' => '07:45:00']);
        $p2 = Period::firstOrCreate(['label' => 'Jam ke-2'], ['start_time' => '07:45:00', 'end_time' => '08:30:00']);
        $p3 = Period::firstOrCreate(['label' => 'Jam ke-3'], ['start_time' => '08:45:00', 'end_time' => '09:30:00']);
        $p4 = Period::firstOrCreate(['label' => 'Jam ke-4'], ['start_time' => '09:30:00', 'end_time' => '10:15:00']);
        $p5 = Period::firstOrCreate(['label' => 'Jam ke-5'], ['start_time' => '10:30:00', 'end_time' => '11:15:00']);
        $p6 = Period::firstOrCreate(['label' => 'Jam ke-6'], ['start_time' => '11:15:00', 'end_time' => '12:00:00']);

        // 8. Schedules contoh Senin - Jumat tanpa bentrok
        // Hari: 1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat
        $schedules = [
            // Senin - X IPA 1
            ['class_id' => $class1->id, 'day_of_week' => 1, 'period_id' => $p1->id, 'subject_id' => $subMath->id, 'teacher_id' => $t1->id],
            ['class_id' => $class1->id, 'day_of_week' => 1, 'period_id' => $p2->id, 'subject_id' => $subMath->id, 'teacher_id' => $t1->id],
            ['class_id' => $class1->id, 'day_of_week' => 1, 'period_id' => $p3->id, 'subject_id' => $subPhys->id, 'teacher_id' => $t3->id],
            ['class_id' => $class1->id, 'day_of_week' => 1, 'period_id' => $p4->id, 'subject_id' => $subPhys->id, 'teacher_id' => $t3->id],

            // Senin - X IPA 2 (Guru berbeda pada slot yang sama)
            ['class_id' => $class2->id, 'day_of_week' => 1, 'period_id' => $p1->id, 'subject_id' => $subIndo->id, 'teacher_id' => $t2->id],
            ['class_id' => $class2->id, 'day_of_week' => 1, 'period_id' => $p2->id, 'subject_id' => $subIndo->id, 'teacher_id' => $t2->id],
            ['class_id' => $class2->id, 'day_of_week' => 1, 'period_id' => $p3->id, 'subject_id' => $subEng->id, 'teacher_id' => $t4->id],
            ['class_id' => $class2->id, 'day_of_week' => 1, 'period_id' => $p4->id, 'subject_id' => $subEng->id, 'teacher_id' => $t4->id],

            // Selasa - X IPA 1
            ['class_id' => $class1->id, 'day_of_week' => 2, 'period_id' => $p1->id, 'subject_id' => $subIndo->id, 'teacher_id' => $t2->id],
            ['class_id' => $class1->id, 'day_of_week' => 2, 'period_id' => $p2->id, 'subject_id' => $subBio->id, 'teacher_id' => $t6->id],

            // Rabu - X IPA 1
            ['class_id' => $class1->id, 'day_of_week' => 3, 'period_id' => $p1->id, 'subject_id' => $subEng->id, 'teacher_id' => $t4->id],
            ['class_id' => $class1->id, 'day_of_week' => 3, 'period_id' => $p2->id, 'subject_id' => $subMath->id, 'teacher_id' => $t1->id],

            // Kamis - X IPA 1
            ['class_id' => $class1->id, 'day_of_week' => 4, 'period_id' => $p1->id, 'subject_id' => $subBio->id, 'teacher_id' => $t6->id],
            ['class_id' => $class1->id, 'day_of_week' => 4, 'period_id' => $p2->id, 'subject_id' => $subPhys->id, 'teacher_id' => $t3->id],

            // Jumat - X IPA 1
            ['class_id' => $class1->id, 'day_of_week' => 5, 'period_id' => $p1->id, 'subject_id' => $subMath->id, 'teacher_id' => $t1->id],
            ['class_id' => $class1->id, 'day_of_week' => 5, 'period_id' => $p2->id, 'subject_id' => $subIndo->id, 'teacher_id' => $t2->id],
        ];

        foreach ($schedules as $data) {
            Schedule::firstOrCreate(
                [
                    'class_id' => $data['class_id'],
                    'day_of_week' => $data['day_of_week'],
                    'period_id' => $data['period_id'],
                ],
                $data
            );
        }
    }
}
