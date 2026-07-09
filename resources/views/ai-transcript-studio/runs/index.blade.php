@extends('ai-transcript-studio.layout')

@section('studio_content')
<div class="mb-3">
    <h4 class="mb-1">AI Run History</h4>
    <p class="text-muted small mb-0">All AI transcript fill runs with progress logs and results.</p>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Target</th>
                    <th>Achieved</th>
                    <th>Bot marks</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td>{{ $run->id }}</td>
                        <td>
                            {{ $run->student?->reg_number }}<br>
                            <span class="small text-muted">{{ $run->student?->fname }} {{ $run->student?->lname }}</span>
                        </td>
                        <td>{{ $run->target_percentage }}% (~{{ $run->target_cgpa }})</td>
                        <td>{{ $run->achieved_cgpa ?? '—' }}</td>
                        <td>{{ $run->submissions_created ?? 0 }}</td>
                        <td>
                            @if ($run->status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif ($run->status === 'running')
                                <span class="badge bg-primary">Running</span>
                            @elseif ($run->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($run->status) }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $run->created_at?->format('d M Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('ai-transcript-studio.run.show', $run) }}" class="btn btn-sm btn-outline-primary">Log</a>
                            @if ($run->student && $run->status === 'completed')
                                <a href="{{ route('ai-transcript-studio.marking.show', $run->student) }}" class="btn btn-sm btn-outline-success">Marks</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No AI runs yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($runs->hasPages())
        <div class="card-footer">{{ $runs->links() }}</div>
    @endif
</div>
@endsection
