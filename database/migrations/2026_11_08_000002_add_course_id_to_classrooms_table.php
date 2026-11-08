<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->constrained()->restrictOnDelete();
        });

        // Preserva turmas de instalações anteriores; o curso pode ser ajustado via API.
        if (DB::table('classrooms')->whereNull('course_id')->exists()) {
            $courseId = DB::table('courses')->insertGetId([
                'name' => 'Curso das turmas existentes',
                'description' => 'Criado na migração para preservar as turmas anteriores.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('classrooms')->whereNull('course_id')->update(['course_id' => $courseId]);
        }

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
