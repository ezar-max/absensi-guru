<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Senin..7=Minggu
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'day_of_week', 'period_id'], 'uq_schedule_class_slot');
            $table->unique(['teacher_id', 'day_of_week', 'period_id'], 'uq_schedule_teacher_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

