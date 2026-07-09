<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'year_index')) {
                $table->unsignedTinyInteger('year_index')->nullable()->after('degree_level_id');
            }
            if (! Schema::hasColumn('courses', 'semester')) {
                $table->unsignedTinyInteger('semester')->nullable()->after('year_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'semester')) {
                $table->dropColumn('semester');
            }
            if (Schema::hasColumn('courses', 'year_index')) {
                $table->dropColumn('year_index');
            }
        });
    }
};
