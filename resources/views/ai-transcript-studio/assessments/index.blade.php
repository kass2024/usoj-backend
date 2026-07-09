@extends('ai-transcript-studio.layout')

@section('studio_content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-1">AI Assessments</h4>
        <p class="text-muted small mb-0">All AI-generated assignments, quizzes, and exams across courses.</p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <option value="assignment" @selected($type === 'assignment')>Assignments (30 marks)</option>
                    <option value="quiz" @selected($type === 'quiz')>Quizzes (30 marks)</option>
                    <option value="exam" @selected($type === 'exam')>Exams (40 marks)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Search course or title</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $search }}"
                       placeholder="Course code, name, or assessment title">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Course</th>
                    <th>Assessment</th>
                    <th class="text-center">Questions</th>
                    <th class="text-center">Submissions</th>
                    <th class="text-end">Max</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assessments as $row)
                    <tr>
                        <td>
                            @if ($row['type'] === 'assignment')
                                <span class="badge bg-primary">Assignment</span>
                            @elseif ($row['type'] === 'quiz')
                                <span class="badge bg-info text-dark">Quiz</span>
                            @else
                                <span class="badge bg-warning text-dark">Exam</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $row['course_code'] }}</strong><br>
                            <span class="small text-muted">{{ Str::limit($row['course_name'], 45) }}</span>
                        </td>
                        <td>{{ $row['title'] }}</td>
                        <td class="text-center">{{ $row['questions_count'] }}</td>
                        <td class="text-center">{{ $row['submissions_count'] }}</td>
                        <td class="text-end">{{ $row['max_marks'] }}</td>
                        <td class="text-end">
                            <a href="{{ route('ai-transcript-studio.assessments.show', [$row['type'], $row['id']]) }}"
                               class="btn btn-outline-success btn-sm">View &amp; marks</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No AI assessments yet. Run <strong>AI Transcript Fill</strong> from Generate Transcript first.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
