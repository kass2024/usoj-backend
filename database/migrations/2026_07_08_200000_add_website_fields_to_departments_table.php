<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('department_degree_level')) {
            Schema::create('department_degree_level', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('degree_level_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['department_id', 'degree_level_id']);
            });
        }

        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'duration')) {
                $table->string('duration')->nullable()->after('description');
            }
            if (!Schema::hasColumn('departments', 'mode')) {
                $table->string('mode')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('departments', 'website_category')) {
                $table->enum('website_category', ['undergraduate', 'diploma', 'short_course'])->nullable()->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'website_category')) {
                $table->dropColumn('website_category');
            }
            if (Schema::hasColumn('departments', 'mode')) {
                $table->dropColumn('mode');
            }
            if (Schema::hasColumn('departments', 'duration')) {
                $table->dropColumn('duration');
            }
        });

        Schema::dropIfExists('department_degree_level');
    }
};
