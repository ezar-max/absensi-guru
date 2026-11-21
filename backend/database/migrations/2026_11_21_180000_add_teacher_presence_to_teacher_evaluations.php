<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_evaluations', function (Blueprint $table) {
            $table->boolean('teacher_present')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_evaluations', function (Blueprint $table) {
            $table->dropColumn('teacher_present');
        });
    }
};
