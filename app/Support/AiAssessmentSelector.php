<?php

namespace App\Support;

use Illuminate\Support\Collection;

class AiAssessmentSelector
{
    public static function pickAssignment(Collection $assignments)
    {
        return self::pick($assignments, 'AI Assignment');
    }

    public static function pickQuiz(Collection $quizzes)
    {
        return self::pick($quizzes, 'AI Quiz');
    }

    public static function pickExam(Collection $exams)
    {
        return self::pick($exams, 'AI Exam');
    }

    private static function pick(Collection $items, string $prefix)
    {
        if ($items->isEmpty()) {
            return null;
        }

        $ai = $items->first(fn ($item) => str_starts_with((string) ($item->title ?? ''), $prefix));

        return $ai ?? $items->sortByDesc('id')->first();
    }
}
