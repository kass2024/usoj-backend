<?php

use App\Models\Department;
use App\Support\CourseDegreeLevelResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'degree_level_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('degree_level_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('degree_levels')
                    ->restrictOnDelete();
            });
        }

        DB::table('courses')
            ->whereNull('degree_level_id')
            ->orderBy('id')
            ->chunkById(100, function ($courses) {
                foreach ($courses as $course) {
                    $department = Department::query()->find($course->department_id);
                    if (! $department) {
                        continue;
                    }

                    $level = CourseDegreeLevelResolver::levelsForDepartment($department)->first();
                    if (! $level) {
                        continue;
                    }

                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update(['degree_level_id' => $level->id]);
                }
            });

        $this->dropSingleColumnUniqueIndexes('courses', ['name', 'code']);

        if (! $this->indexExists('courses', 'courses_code_dept_level_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unique(
                    ['code', 'department_id', 'degree_level_id'],
                    'courses_code_dept_level_unique'
                );
            });
        }

        if (! $this->indexExists('courses', 'courses_name_dept_level_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unique(
                    ['name', 'department_id', 'degree_level_id'],
                    'courses_name_dept_level_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('courses', 'courses_code_dept_level_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropUnique('courses_code_dept_level_unique');
            });
        }

        if ($this->indexExists('courses', 'courses_name_dept_level_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropUnique('courses_name_dept_level_unique');
            });
        }

        if (! $this->indexExists('courses', 'courses_name_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unique('name', 'courses_name_unique');
            });
        }

        if (! $this->indexExists('courses', 'courses_code_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unique('code', 'courses_code_unique');
            });
        }

        if (Schema::hasColumn('courses', 'degree_level_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('degree_level_id');
            });
        }
    }

    /** @param  array<int, string>  $columns */
    protected function dropSingleColumnUniqueIndexes(string $table, array $columns): void
    {
        $database = DB::getDatabaseName();

        $indexes = DB::table('information_schema.statistics')
            ->select('index_name', DB::raw('COUNT(*) as column_count'), DB::raw('MAX(column_name) as column_name'))
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('non_unique', 0)
            ->whereIn('column_name', $columns)
            ->groupBy('index_name')
            ->having('column_count', '=', 1)
            ->get();

        foreach ($indexes as $index) {
            if ($index->index_name === 'PRIMARY') {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index->index_name);
            });
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
