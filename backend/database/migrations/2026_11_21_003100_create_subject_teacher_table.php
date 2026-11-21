<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['subject_id', 'teacher_id']);
        });

        $now = now();
        $assignments = DB::table('schedules')
            ->select('subject_id', 'teacher_id')
            ->distinct()
            ->get();

        foreach ($assignments as $assignment) {
            DB::table('subject_teacher')->insert([
                'subject_id' => $assignment->subject_id,
                'teacher_id' => $assignment->teacher_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_teacher');
    }
};
