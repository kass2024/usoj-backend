@extends('ai-transcript-studio.layout')

@section('studio_content')
@php
    $type = $assessment['type'];
    $maxMarks = $assessment['max_marks'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <a href="{{ route('ai-transcript-studio.assessments.index') }}" class="small text-muted">&larr; All assessments</a>
        <h4 class="mb-1 mt-1">{{ $model->title }}</h4>
        <p class="text-muted small mb-0">
            {{ $assessment['course_code'] }} — {{ $assessment['course_name'] }}
            &middot; Max {{ $maxMarks }} marks &middot; AI auto-marked
        </p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">Student submissions</div>
            <ul class="list-group list-group-flush">
                @forelse ($submissions as $sub)
                    @php $stu = $sub->student; @endphp
                    <a href="{{ route('ai-transcript-studio.assessments.show', [$type, $model->id, 'student_id' => $sub->student_id]) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ ($selectedSubmission?->id ?? null) === $sub->id ? 'active' : '' }}">
                        <span>
                            {{ $stu?->fname }} {{ $stu?->lname }}<br>
                            <small class="{{ ($selectedSubmission?->id ?? null) === $sub->id ? '' : 'text-muted' }}">{{ $stu?->reg_number }}</small>
                        </span>
                        <span class="badge {{ ($selectedSubmission?->id ?? null) === $sub->id ? 'bg-light text-dark' : 'bg-success' }}">
                            {{ $sub->marks_obtained }}/{{ $maxMarks }}
                        </span>
                    </a>
                @empty
                    <li class="list-group-item text-muted">No submissions yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        @if ($selectedSubmission)
            @php
                $stu = $selectedSubmission->student;
                $pct = $maxMarks > 0 ? round(($selectedSubmission->marks_obtained / $maxMarks) * 100, 1) : 0;
                $grades = \App\Support\CertificateGrades::fromPercentage($pct);
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Marking — {{ $stu?->fname }} {{ $stu?->lname }} ({{ $stu?->reg_number }})</span>
                    <span class="badge bg-info">AI Bot</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3">
                            <div class="small text-muted">Score</div>
                            <div class="fs-5 fw-semibold">{{ $selectedSubmission->marks_obtained }}/{{ $maxMarks }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted">Percentage</div>
                            <div class="fs-5 fw-semibold">{{ $pct }}%</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted">GP</div>
                            <div class="fs-5 fw-semibold">{{ number_format($grades['gp'], 2) }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted">Grade</div>
                            <div class="fs-5 fw-semibold">{{ $grades['gd'] }}</div>
                        </div>
                    </div>

                    <form action="{{ route('ai-transcript-studio.marking.update', $selectedSubmission) }}" method="post" class="row g-2 align-items-end">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="assessment_type" value="{{ $type }}">
                        <input type="hidden" name="assessment_id" value="{{ $model->id }}">
                        <div class="col-sm-4">
                            <label class="form-label small">Adjust marks (0–{{ $maxMarks }})</label>
                            <input type="number" name="marks_obtained" class="form-control"
                                   min="0" max="{{ $maxMarks }}" value="{{ $selectedSubmission->marks_obtained }}" required>
                        </div>
                        <div class="col-sm-4">
                            <button type="submit" class="btn btn-outline-primary">Update mark</button>
                        </div>
                    </form>

                    @if ($stu)
                        <div class="mt-3">
                            <a href="{{ route('ai-transcript-studio.marking.show', $stu) }}" class="btn btn-sm btn-outline-secondary">
                                View full student results
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header">Questions ({{ $model->questions->count() }})</div>
            <div class="card-body">
                @forelse ($model->questions as $i => $question)
                    <div class="mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                        <div class="fw-semibold">Q{{ $i + 1 }}. {{ $question->title }}</div>
                        <div class="small text-muted">Type: {{ $question->type }} &middot; Marks: {{ $question->marks }}</div>
                        @if (is_array($question->choices) && $question->choices !== [])
                            <ul class="small mb-0 mt-1">
                                @foreach ($question->choices as $ci => $choice)
                                    <li>{{ $choice }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">No questions stored for this assessment.</p>
                @endforelse
            </div>
        </div>

        @if ($selectedSubmission && is_array($selectedSubmission->answers) && $selectedSubmission->answers !== [])
            <div class="card shadow-sm mt-3">
                <div class="card-header">AI bot answers</div>
                <div class="card-body small">
                    <pre class="mb-0 bg-light p-3 rounded" style="white-space: pre-wrap;">{{ json_encode($selectedSubmission->answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
