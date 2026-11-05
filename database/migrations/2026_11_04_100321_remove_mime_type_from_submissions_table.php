<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('submissions', 'mime_type')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('mime_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('submissions', 'mime_type')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('mime_type')->nullable();
            });
        }
    }
};