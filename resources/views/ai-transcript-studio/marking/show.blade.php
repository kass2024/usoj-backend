@extends('ai-transcript-studio.layout')

@section('studio_content')
<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <a href="{{ route('ai-transcript-studio.marking.index') }}" class="small text-muted">&larr; All students</a>
        <h4 class="mb-1 mt-1">{{ $student->fname }} {{ $student->lname }}</h4>
        <p class="text-muted small mb-0">
            {{ $student->reg_number }}
            &middot; {{ $student->department?->name ?? '—' }}
            @if ($run)
                &middot; Target CGPA {{ number_format($run->target_cgpa, 2) }}
                &middot; Achieved {{ number_format($run->achieved_cgpa ?? 0, 2) }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        @if ($run)
            <a href="{{ route('ai-transcript-studio.run.show', $run) }}" class="btn btn-outline-secondary btn-sm">Run log</a>
        @endif
        <a href="{{ route('ai-transcript-studio.transcript', $student) }}" target="_blank" class="btn btn-success btn-sm">
            <i class="ri-file-pdf-line"></i> Transcript PDF
        </a>
    </div>
</div>

@if (!empty($assessmentResults))
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Assessment marks &amp; transcript grades</span>
            <span class="badge bg-info">AI auto-marked</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Assessment</th>
                        <th class="text-end">Score</th>
                        <th class="text-end">%</th>
                        <th class="text-end">GP</th>
                        <th class="text-center">GD</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assessmentResults as $row)
                        <tr class="{{ !empty($row['is_summary']) ? 'table-primary fw-semibold' : '' }}">
                            <td>
                                <span class="text-muted small">{{ $row['course_code'] }}</span><br>
                                {{ Str::limit($row['course_name'], 35) }}
                            </td>
                            <td>{{ $row['assessment_type'] }}</td>
                            <td>{{ Str::limit($row['assessment_title'], 40) }}</td>
                            <td class="text-end">{{ $row['marks_obtained'] }}/{{ $row['marks_max'] }}</td>
                            <td class="text-end">{{ number_format($row['percentage'], 1) }}%</td>
                            <td class="text-end">{{ number_format($row['gp'], 2) }}</td>
                            <td class="text-center">{{ $row['gd'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            Course total rows show weighted transcript grades calibrated to the target CGPA.
        </div>
    </div>
@else
    <div class="alert alert-warning">
        No AI assessment results for this student. Run <strong>AI Transcript Fill</strong> from Generate Transcript.
    </div>
@endif
@endsection
