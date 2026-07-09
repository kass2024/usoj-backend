<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'gender')) {
                $table->string('gender', 20)->nullable()->after('profile_img');
            }
            if (! Schema::hasColumn('students', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 80)->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('students', 'completion_year')) {
                $table->unsignedSmallInteger('completion_year')->nullable()->after('nationality');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['completion_year', 'nationality', 'date_of_birth', 'gender'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
