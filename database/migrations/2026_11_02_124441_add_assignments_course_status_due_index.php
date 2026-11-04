<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            /*
             * Mendukung query daftar tugas mahasiswa:
             * WHERE course_id = ?
             * AND status = 'published'
             * ORDER BY due_at ASC
             */
            $table->index(
                ['course_id', 'status', 'due_at'],
                'assignments_course_status_due_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('assignments_course_status_due_index');
        });
    }
};