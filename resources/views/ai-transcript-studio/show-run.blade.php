@extends('layouts.app')
@section('body')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">AI Run #{{ $run->id }}</h4>
            <a href="{{ route('ai-transcript-studio.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Student:</strong> {{ $run->student->fname }} {{ $run->student->lname }} ({{ $run->student->reg_number }})</div>
                    <div class="col-md-3"><strong>Target:</strong> {{ $run->target_percentage ?? '—' }}% (~CGPA {{ $run->target_cgpa }})</div>
                    <div class="col-md-2"><strong>Achieved CGPA:</strong> {{ $run->achieved_cgpa ?? '—' }}</div>
                    <div class="col-md-2"><strong>Status:</strong> {{ ucfirst($run->status) }}</div>
                    <div class="col-md-2"><strong>By:</strong> {{ $run->triggeredBy->name ?? 'System' }}</div>
                </div>
                @if ($run->error_message)
                    <div class="alert alert-danger mt-3 mb-0">{{ $run->error_message }}</div>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Progress steps</div>
                    <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                        <ul class="list-unstyled small mb-0">
                            @foreach (($run->progress['steps'] ?? []) as $step)
                                <li class="mb-2 d-flex gap-2">
                                    @if (($step['status'] ?? '') === 'done')
                                        <span class="text-success">✓</span>
                                    @elseif (($step['status'] ?? '') === 'active')
                                        <span class="text-primary">●</span>
                                    @elseif (($step['status'] ?? '') === 'warning')
                                        <span class="text-warning">!</span>
                                    @else
                                        <span class="text-muted">○</span>
                                    @endif
                                    <span>{{ $step['label'] ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">API events (errors &amp; fallbacks)</div>
                    <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                        <ul class="list-unstyled small mb-0">
                            @forelse (($run->progress['events'] ?? []) as $event)
                                <li class="mb-2 pb-2 border-bottom">
                                    <span class="text-muted">{{ $event['time'] ?? '' }}</span>
                                    <span class="badge bg-{{ ($event['type'] ?? '') === 'error' ? 'danger' : (($event['type'] ?? '') === 'fallback' ? 'warning' : 'secondary') }} ms-1">
                                        {{ ucfirst($event['type'] ?? 'info') }}
                                    </span><br>
                                    {{ $event['message'] ?? '' }}
                                    @if (!empty($event['fallback']))
                                        <br><em class="text-warning">Fallback: {{ $event['fallback'] }}</em>
                                    @endif
                                </li>
                            @empty
                                <li class="text-muted">No API events recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">Run log</div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <ul class="list-unstyled small mb-0">
                            @foreach ($run->log ?? [] as $entry)
                                <li class="mb-2">
                                    <span class="text-muted">{{ $entry['time'] ?? '' }}</span><br>
                                    {{ $entry['message'] ?? '' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-3">
                    <div class="card-header">Course materials (PDF)</div>
                    <ul class="list-group list-group-flush">
                        @forelse ($run->materials as $m)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $m->title }}</span>
                                <a href="{{ asset('storage/' . $m->pdf_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">PDF</a>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">None</li>
                        @endforelse
                    </ul>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header">Question bank (reusable)</div>
                    <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                        @forelse ($run->questions as $q)
                            <li class="list-group-item small">
                                <span class="badge bg-secondary">{{ $q->assessment_type }}</span>
                                {{ Str::limit($q->title, 60) }}
                            </li>
                        @empty
                            <li class="list-group-item text-muted">None</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        @if ($run->status === 'completed')
            <div class="mt-4">
                <a href="{{ route('certificates.transcript', encrypt($run->student_id)) }}" target="_blank" class="btn btn-success btn-lg">
                    <i class="ri-file-pdf-line"></i> Generate Transcript Now
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
