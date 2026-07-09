<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Exam;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AiAssessmentCatalog
{
    public const ASSIGNMENT_PREFIX = 'AI Assignment';
    public const QUIZ_PREFIX = 'AI Quiz';
    public const EXAM_PREFIX = 'AI Exam';

    public static function assignmentQuery(): Builder
    {
        return Assignment::query()
            ->where('title', 'like', self::ASSIGNMENT_PREFIX . '%')
            ->with(['module.course', 'questions', 'submissions.student']);
    }

    public static function quizQuery(): Builder
    {
        return Quiz::query()
            ->where('title', 'like', self::QUIZ_PREFIX . '%')
            ->with(['module.course', 'questions', 'submissions.student']);
    }

    public static function examQuery(): Builder
    {
        return Exam::query()
            ->where('title', 'like', self::EXAM_PREFIX . '%')
            ->with(['module.course', 'questions', 'submissions.student']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function allAssessments(?string $type = null, ?string $search = null): Collection
    {
        $rows = collect();

        if (! $type || $type === 'assignment') {
            $rows = $rows->merge(self::mapAssessments(self::assignmentQuery()->get(), 'assignment', 30));
        }

        if (! $type || $type === 'quiz') {
            $rows = $rows->merge(self::mapAssessments(self::quizQuery()->get(), 'quiz', 30));
        }

        if (! $type || $type === 'exam') {
            $rows = $rows->merge(self::mapAssessments(self::examQuery()->get(), 'exam', 40));
        }

        if ($search) {
            $needle = mb_strtolower(trim($search));
            $rows = $rows->filter(function (array $row) use ($needle) {
                return str_contains(mb_strtolower($row['title']), $needle)
                    || str_contains(mb_strtolower($row['course_code']), $needle)
                    || str_contains(mb_strtolower($row['course_name']), $needle);
            });
        }

        return $rows->sortBy(['course_code', 'type', 'title'])->values();
    }

    /**
     * @param  Collection<int, Assignment|Quiz|Exam>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private static function mapAssessments(Collection $items, string $type, int $maxMarks): Collection
    {
        return $items->map(function ($item) use ($type, $maxMarks) {
            $course = $item->module?->course;

            return [
                'id' => $item->id,
                'type' => $type,
                'title' => $item->title,
                'course_code' => $course->code ?? '—',
                'course_name' => $course->name ?? '—',
                'module_id' => $item->module_id,
                'questions_count' => $item->questions->count(),
                'submissions_count' => $item->submissions->count(),
                'max_marks' => $maxMarks,
            ];
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findAssessment(string $type, int $id): ?array
    {
        $model = match ($type) {
            'assignment' => self::assignmentQuery()->find($id),
            'quiz' => self::quizQuery()->find($id),
            'exam' => self::examQuery()->find($id),
            default => null,
        };

        if (! $model) {
            return null;
        }

        $max = match ($type) {
            'assignment', 'quiz' => 30,
            'exam' => 40,
            default => 0,
        };

        $course = $model->module?->course;

        return [
            'model' => $model,
            'type' => $type,
            'max_marks' => $max,
            'course_code' => $course->code ?? '—',
            'course_name' => $course->name ?? '—',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function studentsWithAiMarks(?string $search = null): Collection
    {
        $studentIds = Submission::query()
            ->where(function (Builder $q) {
                $q->whereHas('assignment', fn (Builder $qq) => $qq->where('title', 'like', self::ASSIGNMENT_PREFIX . '%'))
                    ->orWhereHas('quiz', fn (Builder $qq) => $qq->where('title', 'like', self::QUIZ_PREFIX . '%'))
                    ->orWhereHas('exam', fn (Builder $qq) => $qq->where('title', 'like', self::EXAM_PREFIX . '%'));
            })
            ->distinct()
            ->pluck('student_id');

        $query = Student::query()
            ->with(['department'])
            ->whereIn('id', $studentIds);

        if ($search) {
            $needle = '%' . trim($search) . '%';
            $query->where(function (Builder $q) use ($needle) {
                $q->where('reg_number', 'like', $needle)
                    ->orWhere('fname', 'like', $needle)
                    ->orWhere('lname', 'like', $needle);
            });
        }

        return $query->orderBy('reg_number')->get()->map(function (Student $student) {
            $submissions = Submission::query()
                ->where('student_id', $student->id)
                ->where(function (Builder $q) {
                    $q->whereHas('assignment', fn (Builder $qq) => $qq->where('title', 'like', self::ASSIGNMENT_PREFIX . '%'))
                        ->orWhereHas('quiz', fn (Builder $qq) => $qq->where('title', 'like', self::QUIZ_PREFIX . '%'))
                        ->orWhereHas('exam', fn (Builder $qq) => $qq->where('title', 'like', self::EXAM_PREFIX . '%'));
                })
                ->count();

            $latestRun = \App\Models\AiTranscriptRun::query()
                ->where('student_id', $student->id)
                ->where('status', 'completed')
                ->latest()
                ->first();

            return [
                'student' => $student,
                'submission_count' => $submissions,
                'achieved_cgpa' => $latestRun?->achieved_cgpa,
                'target_cgpa' => $latestRun?->target_cgpa,
                'latest_run_id' => $latestRun?->id,
            ];
        });
    }

    public static function counts(): array
    {
        return [
            'assignments' => Assignment::where('title', 'like', self::ASSIGNMENT_PREFIX . '%')->count(),
            'quizzes' => Quiz::where('title', 'like', self::QUIZ_PREFIX . '%')->count(),
            'exams' => Exam::where('title', 'like', self::EXAM_PREFIX . '%')->count(),
            'submissions' => Submission::query()
                ->where(function (Builder $q) {
                    $q->whereHas('assignment', fn (Builder $qq) => $qq->where('title', 'like', self::ASSIGNMENT_PREFIX . '%'))
                        ->orWhereHas('quiz', fn (Builder $qq) => $qq->where('title', 'like', self::QUIZ_PREFIX . '%'))
                        ->orWhereHas('exam', fn (Builder $qq) => $qq->where('title', 'like', self::EXAM_PREFIX . '%'));
                })
                ->count(),
        ];
    }
}
