@extends('ai-transcript-studio.layout')

@section('studio_content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-1">AI Marking &amp; Results</h4>
        <p class="text-muted small mb-0">Students with AI auto-marked assignments, quizzes, and exams.</p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label small mb-1">Search student</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $search }}"
                       placeholder="Registration number or name">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success btn-sm w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Program</th>
                    <th class="text-center">AI marks</th>
                    <th class="text-center">Target CGPA</th>
                    <th class="text-center">Achieved CGPA</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $row)
                    @php $stu = $row['student']; @endphp
                    <tr>
                        <td>
                            <strong>{{ $stu->fname }} {{ $stu->lname }}</strong><br>
                            <span class="small text-muted">{{ $stu->reg_number }}</span>
                        </td>
                        <td class="small">{{ $stu->department?->name ?? '—' }}</td>
                        <td class="text-center">{{ $row['submission_count'] }}</td>
                        <td class="text-center">{{ $row['target_cgpa'] ? number_format($row['target_cgpa'], 2) : '—' }}</td>
                        <td class="text-center">
                            @if ($row['achieved_cgpa'])
                                <span class="badge bg-success">{{ number_format($row['achieved_cgpa'], 2) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('ai-transcript-studio.marking.show', $stu) }}" class="btn btn-outline-success btn-sm">
                                View results
                            </a>
                            @if ($row['latest_run_id'])
                                <a href="{{ route('ai-transcript-studio.run.show', $row['latest_run_id']) }}" class="btn btn-outline-secondary btn-sm">Run log</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No AI marks found. Run <strong>AI Transcript Fill</strong> for a student first.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
