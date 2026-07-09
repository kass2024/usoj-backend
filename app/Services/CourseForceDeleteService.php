<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Modules;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

class CourseForceDeleteService
{
    /** @return array{modules: int, assignments: int, quizzes: int, exams: int, lessons: int} */
    public function delete(Course $course): array
    {
        $counts = [
            'modules' => 0,
            'assignments' => 0,
            'quizzes' => 0,
            'exams' => 0,
            'lessons' => 0,
        ];

        DB::transaction(function () use ($course, &$counts) {
            $modules = Modules::query()->where('course_id', $course->id)->get();

            foreach ($modules as $module) {
                $counts['assignments'] += Assignment::query()->where('module_id', $module->id)->delete();
                $counts['quizzes'] += Quiz::query()->where('module_id', $module->id)->delete();
                $counts['exams'] += Exam::query()->where('module_id', $module->id)->delete();
                $counts['lessons'] += Lesson::query()->where('module_id', $module->id)->delete();
                $module->delete();
                $counts['modules']++;
            }

            $course->delete();
        });

        return $counts;
    }

    public function summaryMessage(string $label, array $counts): string
    {
        if ($counts['modules'] === 0) {
            return sprintf('Course "%s" deleted successfully.', $label);
        }

        return sprintf(
            'Course "%s" force-deleted with %d module(s), %d assignment(s), %d quiz(zes), %d exam(s), and %d lesson(s).',
            $label,
            $counts['modules'],
            $counts['assignments'],
            $counts['quizzes'],
            $counts['exams'],
            $counts['lessons']
        );
    }
}
