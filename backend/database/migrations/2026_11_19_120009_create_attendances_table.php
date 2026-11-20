<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->date('date')->index();
            $table->enum('status', ['hadir', 'terlambat', 'kosong', 'izin', 'sakit', 'dinas']);
            $table->time('arrived_at')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['schedule_id', 'date'], 'uq_attendance_schedule_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

