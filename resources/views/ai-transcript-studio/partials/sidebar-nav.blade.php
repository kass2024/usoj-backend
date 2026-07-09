@php
    $counts = $counts ?? \App\Support\AiAssessmentCatalog::counts();
@endphp

<div class="card shadow-sm border-0 ai-studio-nav sticky-top" style="top: 90px;">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-robot-2-line text-success fs-4"></i>
            <div>
                <div class="fw-semibold">AI Transcript Studio</div>
                <div class="ai-studio-stat">Assessments &amp; marking</div>
            </div>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('ai-transcript-studio.index') }}"
           class="list-group-item list-group-item-action {{ request()->routeIs('ai-transcript-studio.index') ? 'active' : '' }}">
            <i class="ri-file-pdf-line me-2"></i> Generate Transcript
        </a>
        <a href="{{ route('ai-transcript-studio.assessments.index') }}"
           class="list-group-item list-group-item-action {{ request()->routeIs('ai-transcript-studio.assessments.*') ? 'active' : '' }}">
            <i class="ri-file-list-3-line me-2"></i> AI assessments
            <span class="badge bg-secondary float-end">{{ $counts['assignments'] + $counts['quizzes'] + $counts['exams'] }}</span>
        </a>
        <a href="{{ route('ai-transcript-studio.marking.index') }}"
           class="list-group-item list-group-item-action {{ request()->routeIs('ai-transcript-studio.marking.*') ? 'active' : '' }}">
            <i class="ri-mark-pen-line me-2"></i> Marking &amp; results
            <span class="badge bg-info float-end">{{ $counts['submissions'] }}</span>
        </a>
        <a href="{{ route('ai-transcript-studio.runs.index') }}"
           class="list-group-item list-group-item-action {{ request()->routeIs('ai-transcript-studio.runs.*') || request()->routeIs('ai-transcript-studio.run.show') ? 'active' : '' }}">
            <i class="ri-history-line me-2"></i> Run history
        </a>
    </div>
    <div class="card-body border-top py-3 small text-muted">
        <div class="mb-1"><i class="ri-book-2-line me-1"></i> Assignments: {{ $counts['assignments'] }}</div>
        <div class="mb-1"><i class="ri-questionnaire-line me-1"></i> Quizzes: {{ $counts['quizzes'] }}</div>
        <div class="mb-1"><i class="ri-file-edit-line me-1"></i> Exams: {{ $counts['exams'] }}</div>
        <div><i class="ri-checkbox-circle-line me-1"></i> Bot marks: {{ $counts['submissions'] }}</div>
    </div>
</div>
