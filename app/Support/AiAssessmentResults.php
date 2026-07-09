<?php

namespace App\Support;

use App\Models\AiTranscriptRun;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\Modules;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\Submission;

class AiAssessmentResults
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forRun(Student $student, AiTranscriptRun $run): array
    {
        $gradePlan = $run->options['grade_plan'] ?? [];
        $rows = [];

        $modules = Modules::query()
            ->with([
                'course',
                'assignments.submissions' => fn ($q) => $q->where('student_id', $student->id),
                'quizzes.submissions' => fn ($q) => $q->where('student_id', $student->id),
                'exams.submissions' => fn ($q) => $q->where('student_id', $student->id),
            ])
            ->whereHas('course')
            ->where(function ($q) use ($student) {
                $q->whereHas('assignments.submissions', fn ($qq) => $qq->where('student_id', $student->id))
                    ->orWhereHas('quizzes.submissions', fn ($qq) => $qq->where('student_id', $student->id))
                    ->orWhereHas('exams.submissions', fn ($qq) => $qq->where('student_id', $student->id));
            })
            ->get();

        foreach ($modules as $module) {
            $course = $module->course;
            if (! $course) {
                continue;
            }

            $plan = $gradePlan[$course->id] ?? $gradePlan[(string) $course->id] ?? null;

            foreach ([
                ['type' => 'Assignment', 'model' => AiAssessmentSelector::pickAssignment($module->assignments), 'max' => 30],
                ['type' => 'Quiz', 'model' => AiAssessmentSelector::pickQuiz($module->quizzes), 'max' => 30],
                ['type' => 'Exam', 'model' => AiAssessmentSelector::pickExam($module->exams), 'max' => 40],
            ] as $slot) {
                /** @var Assignment|Quiz|Exam|null $assessment */
                $assessment = $slot['model'];
                if (! $assessment) {
                    continue;
                }

                $submission = $assessment->submissions->first();
                if (! $submission) {
                    continue;
                }

                $obtained = (int) ($submission->marks_obtained ?? 0);
                $max = (int) $slot['max'];
                $pct = $max > 0 ? round(($obtained / $max) * 100, 1) : 0.0;
                $grades = CertificateGrades::fromPercentage($pct);

                $rows[] = [
                    'course_code' => $course->code,
                    'course_name' => $course->name,
                    'assessment_type' => $slot['type'],
                    'assessment_title' => $assessment->title,
                    'marks_obtained' => $obtained,
                    'marks_max' => $max,
                    'percentage' => $pct,
                    'gp' => $grades['gp'],
                    'gd' => $grades['gd'],
                    'planned_gp' => $plan['gp'] ?? null,
                    'source' => 'ai_bot',
                ];
            }

            if ($plan) {
                $rows[] = [
                    'course_code' => $course->code,
                    'course_name' => $course->name,
                    'assessment_type' => 'Course total',
                    'assessment_title' => 'Weighted transcript grade',
                    'marks_obtained' => (int) (($plan['percentage'] ?? 0)),
                    'marks_max' => 100,
                    'percentage' => (float) ($plan['percentage'] ?? 0),
                    'gp' => (float) ($plan['gp'] ?? 0),
                    'gd' => (string) ($plan['gd'] ?? CertificateGrades::fromPercentage((float) ($plan['percentage'] ?? 0))['gd']),
                    'planned_gp' => (float) ($plan['gp'] ?? 0),
                    'source' => 'ai_plan',
                    'is_summary' => true,
                ];
            }
        }

        usort($rows, fn ($a, $b) => [$a['course_code'], $a['is_summary'] ?? false] <=> [$b['course_code'], $b['is_summary'] ?? false]);

        return $rows;
    }

    /**
     * @return array<int, array{gp: float, percentage: float, marks: array<string, int>}>|null
     */
    public static function gradePlanForStudent(Student $student): ?array
    {
        $run = AiTranscriptRun::query()
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (! $run) {
            return null;
        }

        $plan = $run->options['grade_plan'] ?? null;

        return is_array($plan) && $plan !== [] ? $plan : null;
    }
}
