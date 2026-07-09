@extends('layouts.app')
@section('body')
<div class="row">
    <div class="col-12">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #007a33 0%, #005a26 100%);">
            <div class="card-body text-white py-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4 class="text-white mb-1"><i class="ri-robot-2-line me-2"></i>AI Transcript Studio</h4>
                        <p class="mb-0 opacity-75 small">
                            <strong>AI mode</strong> auto-fills programs by degree level:
                            Bachelor = 4 years × 2 semesters, Master = 2 years × 2 semesters.
                            <strong>Manual mode</strong> is still available under
                            <a href="{{ route('certificates.index') }}" class="text-white text-decoration-underline">Generate Academic Docs</a>.
                        </p>
                    </div>
                    <div class="text-end">
                        @if ($gemini->isConfigured())
                            <span class="badge bg-success fs-6"><i class="ri-check-line"></i> Gemini connected</span>
                        @else
                            <span class="badge bg-warning text-dark fs-6">Set GOOGLE_AI_API_KEY in .env</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@isset($student)
    @php
        $transcriptReady = \App\Support\TranscriptProfile::isReady($student);
        $missingProfile = \App\Support\TranscriptProfile::missingFields($student);
        $pdfUnlocked = ($aiFillCompleted ?? false) && $transcriptReady;
    @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="ai-workflow d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="ai-workflow-step {{ $student ? 'is-done' : 'is-active' }}">
                            <span class="ai-workflow-icon">1</span>
                            <div>
                                <div class="fw-semibold">Find student</div>
                                <div class="small text-muted">{{ $student->reg_number }}</div>
                            </div>
                        </div>
                        <div class="ai-workflow-arrow d-none d-md-block">→</div>
                        <div class="ai-workflow-step {{ ($aiFillCompleted ?? false) ? 'is-done' : (($activeAiRun ?? null) ? 'is-active' : 'is-pending') }}" id="workflow-step-ai">
                            <span class="ai-workflow-icon">2</span>
                            <div>
                                <div class="fw-semibold">Run AI fill</div>
                                <div class="small text-muted" id="workflow-ai-hint">
                                    @if ($aiFillCompleted ?? false)
                                        Materials, quizzes &amp; marks ready
                                    @elseif ($activeAiRun ?? null)
                                        Running now…
                                    @else
                                        Auto materials, quizzes, marking
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="ai-workflow-arrow d-none d-md-block">→</div>
                        <div class="ai-workflow-step {{ $pdfUnlocked ? 'is-done' : 'is-pending' }}" id="workflow-step-pdf">
                            <span class="ai-workflow-icon">3</span>
                            <div>
                                <div class="fw-semibold">Generate PDF</div>
                                <div class="small text-muted" id="workflow-pdf-hint">
                                    @if ($pdfUnlocked)
                                        Transcript ready to download
                                    @elseif (!($aiFillCompleted ?? false))
                                        Unlocks after AI fill completes
                                    @else
                                        Complete gender &amp; date of birth
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endisset

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">1. Find student</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('ai-transcript-studio.lookup') }}" method="post" class="mb-0">
                    @csrf
                    <label class="form-label text-muted">Registration number</label>
                    <div class="input-group mb-3">
                        <input type="text" name="reg_number" class="form-control"
                               value="{{ old('reg_number', $student->reg_number ?? '') }}"
                               placeholder="e.g. 21MEE001" required>
                        <button class="btn btn-primary">Find</button>
                    </div>
                </form>

                @isset($student)
                    <div class="border rounded p-3 bg-light">
                        <h6 class="mb-2">{{ $student->fname }} {{ $student->lname }}</h6>
                        <p class="mb-1 small"><strong>Reg:</strong> {{ $student->reg_number }}</p>
                        <p class="mb-1 small"><strong>Gender:</strong> {{ \App\Support\TranscriptProfile::genderLabel($student) }}</p>
                        <p class="mb-1 small"><strong>Date of Birth:</strong> {{ \App\Support\TranscriptProfile::dateOfBirthFormatted($student) }}</p>
                        <p class="mb-1 small"><strong>Nationality:</strong> {{ \App\Support\TranscriptProfile::nationality($student) }}</p>
                        <p class="mb-1 small"><strong>Completion Year:</strong> {{ \App\Support\TranscriptProfile::completionYear($student) }} <span class="text-muted">(auto)</span></p>
                        @if ($student->department)
                            <p class="mb-1 small text-muted">{{ $student->department->name }}</p>
                        @endif
                        @if ($student->degree_level)
                            <p class="mb-1 small">
                                <strong>Level:</strong> {{ $student->degree_level->name }}
                            </p>
                            <p class="mb-2 small text-primary fw-semibold">
                                {{ \App\Support\ProgramDuration::structureLabel($student->degree_level) }}
                            </p>
                        @endif
                        @if ($courseCount > 0)
                            <p class="mb-2 small">
                                <strong>{{ $courseCount }}</strong> courses scheduled across
                                <strong>{{ $semesterSlots ?? 8 }}</strong> semesters
                            </p>
                        @endif
                        @if (!empty($scheduleSummary))
                            <div class="small border-top pt-2 mt-2">
                                <div class="fw-semibold mb-1">Course placement preview</div>
                                <ul class="list-unstyled mb-0">
                                    @foreach ($scheduleSummary as $slot)
                                        <li class="mb-1">
                                            <span class="text-muted">Y{{ $slot['year_index'] }} S{{ $slot['semester'] }}:</span>
                                            {{ implode(', ', $slot['courses'] ?? []) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($student)
                            <div class="mt-3 border-top pt-3" id="transcript-pdf-panel"
                                 data-transcript-url="{{ route('ai-transcript-studio.transcript', $student) }}"
                                 data-profile-ready="{{ $transcriptReady ? '1' : '0' }}"
                                 data-ai-completed="{{ ($aiFillCompleted ?? false) ? '1' : '0' }}">
                                <div class="fw-semibold mb-2">3. Transcript PDF</div>

                                @if ($completedAiRun ?? null)
                                    <div class="small mb-2" id="ai-run-summary">
                                        <span class="badge bg-success-subtle text-success me-1">AI done</span>
                                        {{ $completedAiRun->courses_processed }} courses ·
                                        {{ $completedAiRun->materials_created }} PDFs ·
                                        {{ $completedAiRun->questions_saved }} questions ·
                                        {{ $completedAiRun->submissions_created }} marks
                                        @if ($completedAiRun->achieved_cgpa)
                                            · CGPA {{ number_format($completedAiRun->achieved_cgpa, 2) }}
                                        @endif
                                    </div>
                                @else
                                    <div class="small text-muted mb-2" id="ai-run-summary">
                                        Run <strong>AI Transcript Fill</strong> on the right to unlock the PDF.
                                    </div>
                                @endif

                                <div id="transcript-pdf-actions">
                                    @if ($pdfUnlocked)
                                        <a href="{{ route('ai-transcript-studio.transcript', $student) }}" target="_blank"
                                           class="btn btn-primary btn-sm w-100" id="transcript-pdf-btn">
                                            <i class="ri-file-pdf-line"></i> Generate Transcript PDF
                                        </a>
                                    @elseif ($aiFillCompleted ?? false)
                                        <button type="button" class="btn btn-primary btn-sm w-100" id="transcript-pdf-btn"
                                                data-bs-toggle="modal" data-bs-target="#aiTranscriptProfileModal">
                                            <i class="ri-file-pdf-line"></i> Complete profile &amp; generate PDF
                                        </button>
                                        <div class="small text-warning mt-2">
                                            Missing: {{ \App\Support\TranscriptProfile::missingFieldsLabel($student) }}
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-secondary btn-sm w-100" id="transcript-pdf-btn" disabled>
                                            <i class="ri-lock-line"></i> Generate Transcript PDF
                                        </button>
                                        <div class="small text-muted mt-2" id="transcript-pdf-hint">
                                            Locked until AI fill completes successfully.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endisset
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">2. AI settings &amp; run</h5>
            </div>
            <div class="card-body">
                @isset($student)
                    <form action="{{ route('ai-transcript-studio.run') }}" method="post" id="ai-run-form">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                        <input type="hidden" name="target_percentage" id="target_percentage_hidden" value="{{ $estimatedPercentage ?? 76 }}">

                        <div class="mb-3">
                            <label class="form-label">Target scale</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="target_scale" id="scale_cgpa" value="cgpa"
                                       @checked(($targetScale ?? 'cgpa') === 'cgpa')>
                                <label class="btn btn-outline-primary" for="scale_cgpa">CGPA (2.0 – 5.0)</label>

                                <input type="radio" class="btn-check" name="target_scale" id="scale_percentage" value="percentage"
                                       @checked(($targetScale ?? 'cgpa') === 'percentage')>
                                <label class="btn btn-outline-primary" for="scale_percentage">Percentage (45% – 95%)</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span id="target-label">Target CGPA on transcript</span>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="target_value" id="target_value"
                                           class="form-control form-control-sm text-end"
                                           style="width: 90px;"
                                           step="0.01"
                                           value="{{ number_format($targetValue ?? 4.5, 2, '.', '') }}">
                                    <strong id="target-unit" class="text-nowrap">CGPA</strong>
                                </div>
                            </label>
                            <input type="range" class="form-range" id="target_slider"
                                   min="2" max="5" step="0.01"
                                   value="{{ $targetValue ?? 4.5 }}">
                            <div class="d-flex justify-content-between small text-muted" id="scale-hints">
                                <span>2.0 Pass</span>
                                <span>4.4 First Class</span>
                                <span>5.0</span>
                            </div>
                            @if ($estimatedClass)
                                <div class="alert alert-info py-2 mt-2 mb-0 small">
                                    Estimated class: <strong>{{ $estimatedClass }}</strong>
                                    @if ($estimatedCgpa && $estimatedPercentage)
                                        (CGPA {{ number_format($estimatedCgpa, 2) }} ≈ {{ number_format($estimatedPercentage, 1) }}%)
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="fast_mode" value="1" id="opt_fast" checked>
                                    <label class="form-check-label" for="opt_fast">
                                        <strong>Fast mode</strong><br>
                                        <small class="text-muted">Skip Gemini API wait — built-in questions, no PDFs</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="generate_materials" value="1" id="opt_materials" checked>
                                    <label class="form-check-label" for="opt_materials">
                                        <strong>Course materials</strong><br>
                                        <small class="text-muted">Save as PDF</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="generate_assessments" value="1" id="opt_assessments" checked>
                                    <label class="form-check-label" for="opt_assessments">
                                        <strong>Assessments</strong><br>
                                        <small class="text-muted">AI questions → bank</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="bot_auto_mark" value="1" id="opt_bot" checked>
                                    <label class="form-check-label" for="opt_bot">
                                        <strong>Bot auto-mark</strong><br>
                                        <small class="text-muted">No student answers</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border small mb-3">
                            <i class="ri-information-line"></i>
                            <strong>Manual PDF</strong> (Generate Academic Docs) uses the <strong>old logic</strong> —
                            it only reads marks from the <code>submissions</code> table and prints the transcript.
                            It does not change any marks.
                        </div>

                        <div class="alert alert-warning small mb-3">
                            <i class="ri-information-line"></i>
                            <strong>AI fill</strong> saves marks to the same <code>submissions</code> table and
                            <strong>overwrites</strong> any previous marks for this student (manual or AI).
                            Bot assigns unique per-course percentages. Students never answer assessments.
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100" id="ai-run-btn" @disabled(!$gemini->isConfigured())>
                            <i class="ri-magic-line me-1"></i> Run AI Transcript Fill
                        </button>
                    </form>
                @else
                    <p class="text-muted mb-0">Find a student first to configure AI transcript generation.</p>
                @endisset
            </div>
        </div>
    </div>
</div>

@if ($lastRun && session('last_run_id') == $lastRun->id && $lastRun->status === 'completed')
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="text-white mb-0">Last run summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3 mb-3">
                        <div class="col-md-2"><div class="fs-4 fw-bold text-success">{{ $lastRun->courses_processed }}</div><small>Courses</small></div>
                        <div class="col-md-2"><div class="fs-4 fw-bold text-primary">{{ $lastRun->materials_created }}</div><small>PDFs</small></div>
                        <div class="col-md-2"><div class="fs-4 fw-bold text-info">{{ $lastRun->questions_saved }}</div><small>Questions</small></div>
                        <div class="col-md-2"><div class="fs-4 fw-bold text-warning">{{ $lastRun->submissions_created }}</div><small>Bot marks</small></div>
                        <div class="col-md-2"><div class="fs-4 fw-bold">{{ $lastRun->target_percentage ?? '—' }}%</div><small>Target %</small></div>
                        <div class="col-md-2"><div class="fs-4 fw-bold">{{ $lastRun->target_cgpa }}</div><small>Est. CGPA</small></div>
                        <div class="col-md-2"><div class="fs-4 fw-bold text-success">{{ $lastRun->achieved_cgpa }}</div><small>Achieved</small></div>
                    </div>
                    @if ($student && $transcriptReady)
                        <a href="{{ route('ai-transcript-studio.transcript', $student) }}" target="_blank" class="btn btn-primary">
                            <i class="ri-file-pdf-line"></i> Generate Transcript PDF
                        </a>
                    @elseif ($student)
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aiTranscriptProfileModal">
                            <i class="ri-file-pdf-line"></i> Complete profile &amp; generate PDF
                        </button>
                    @endif
                    <a href="{{ route('ai-transcript-studio.run.show', $lastRun) }}" class="btn btn-outline-secondary">View full log</a>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Recent AI runs</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Target %</th>
                            <th>Achieved CGPA</th>
                            <th>PDFs</th>
                            <th>Questions</th>
                            <th>Bot marks</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentRuns as $run)
                            <tr>
                                <td>{{ $run->student->reg_number ?? '—' }}</td>
                                <td>{{ $run->target_percentage ?? '—' }}%</td>
                                <td>{{ $run->achieved_cgpa ?? '—' }}</td>
                                <td>{{ $run->materials_created }}</td>
                                <td>{{ $run->questions_saved }}</td>
                                <td>{{ $run->submissions_created }}</td>
                                <td>
                                    @php
                                        $statusColor = match ($run->status) {
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'cancelled' => 'warning',
                                            'running' => 'primary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}">
                                        {{ ucfirst($run->status) }}
                                    </span>
                                </td>
                                <td>{{ $run->created_at->format('d M Y H:i') }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('ai-transcript-studio.run.show', $run) }}" class="btn btn-sm btn-outline-primary">Log</a>
                                    @if ($run->student && $run->status === 'completed')
                                        <a href="{{ route('ai-transcript-studio.transcript', $run->student) }}" target="_blank" class="btn btn-sm btn-outline-success">PDF</a>
                                    @endif
                                    @if ($run->isActive())
                                        <form action="{{ route('ai-transcript-studio.run.cancel', $run) }}" method="post" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Stop</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('ai-transcript-studio.run.destroy', $run) }}" method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this AI run from the list?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No AI runs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="ai-overlay" id="ai-overlay">
    <div class="ai-panel ai-panel-wide">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="mb-1"><i class="ri-robot-2-line text-success"></i> AI Transcript Generation</h5>
                <p class="text-muted small mb-0" id="ai-status-text">Starting…</p>
            </div>
            <span class="badge bg-success fs-6" id="ai-percent-badge">0%</span>
        </div>

        <div class="progress mb-3" style="height: 14px; border-radius: 8px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                 id="ai-progress-bar" role="progressbar" style="width: 0%"></div>
        </div>

        <div class="mb-3 d-none" id="ai-stop-wrap">
            <button type="button" class="btn btn-outline-warning btn-sm" id="ai-stop-btn">
                <i class="ri-stop-circle-line"></i> Stop run
            </button>
        </div>

        <div class="ai-steps-box mb-3" id="ai-steps-list">
            <p class="text-muted small mb-0 text-center py-2">Waiting for steps…</p>
        </div>

        <div class="ai-events-box" id="ai-events-list">
            <div class="small text-muted fw-semibold mb-2">API log (errors &amp; fallbacks)</div>
            <div id="ai-events-inner" class="small text-muted">No events yet.</div>
        </div>

        <div class="mt-3 d-none" id="ai-done-actions">
            <div id="ai-overlay-pdf-wrap" class="d-none">
                <a href="#" target="_blank" class="btn btn-primary btn-sm me-2" id="ai-overlay-pdf-btn">
                    <i class="ri-file-pdf-line"></i> Generate Transcript PDF
                </a>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="ai-close-overlay">Close</button>
        </div>
    </div>
</div>

@isset($student)
<div class="modal fade" id="aiTranscriptProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('certificates.transcript.profile', encrypt($student->id)) }}">
            @csrf
            <input type="hidden" name="generate_after" value="1">
            <div class="modal-header">
                <h5 class="modal-title">Complete transcript profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Gender and date of birth are required before generating the official USJ transcript.</p>
                <div class="mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select gender</option>
                        <option value="MALE" @selected(strtoupper((string) $student->gender) === 'MALE')>Male</option>
                        <option value="FEMALE" @selected(strtoupper((string) $student->gender) === 'FEMALE')>Female</option>
                        <option value="OTHER" @selected(strtoupper((string) $student->gender) === 'OTHER')>Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" max="{{ date('Y-m-d') }}"
                           value="{{ $student->date_of_birth?->format('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nationality</label>
                    <input type="text" name="nationality" class="form-control"
                           value="{{ $student->nationality }}" placeholder="e.g. UGANDAN">
                </div>
                <div class="mb-0">
                    <div class="small text-muted">
                        Completion year is calculated automatically:
                        {{ \App\Support\StudentCompletionYear::explanation($student) }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save &amp; generate transcript</button>
            </div>
        </form>
    </div>
</div>
@endisset

@if (isset($student) && session('transcript_profile_required'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('aiTranscriptProfileModal');
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });
</script>
@endif

<style>
    .ai-overlay {
        position: fixed; inset: 0; background: rgba(0,40,20,.72);
        display: none; align-items: center; justify-content: center; z-index: 9999;
        padding: 16px;
    }
    .ai-overlay.active { display: flex; }
    .ai-panel {
        background: #fff; border-radius: 16px; padding: 24px;
        width: 92%; max-width: 420px;
        box-shadow: 0 20px 50px rgba(0,0,0,.25);
        max-height: 90vh; overflow-y: auto;
    }
    .ai-panel-wide { max-width: 560px; }
    .ai-steps-box {
        max-height: 220px; overflow-y: auto;
        border: 1px solid #e9ecef; border-radius: 10px; padding: 10px;
        background: #f8f9fa;
    }
    .ai-step-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 8px 10px; font-size: 0.85rem; border-bottom: 1px solid #eee;
        border-radius: 8px; margin-bottom: 4px;
        transition: background .2s, border-color .2s;
    }
    .ai-step-item:last-child { border-bottom: none; margin-bottom: 0; }
    .ai-step-item--pending { color: #6c757d; background: #fff; }
    .ai-step-item--active {
        color: #0f5132; background: #d1e7dd; border: 1px solid #a3cfbb;
        font-weight: 600;
    }
    .ai-step-item--done { color: #198754; background: #f0fff4; }
    .ai-step-item--warning { color: #b45309; background: #fff7ed; }
    .ai-step-icon {
        width: 22px; height: 22px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: 14px; font-weight: 700;
    }
    .ai-step-icon--pending { color: #adb5bd; border: 2px solid #dee2e6; background: #fff; }
    .ai-step-icon--active { color: #fff; background: #198754; border: 2px solid #198754; }
    .ai-step-icon--done { color: #fff; background: #198754; border: 2px solid #198754; }
    .ai-step-icon--warning { color: #fff; background: #fd7e14; border: 2px solid #fd7e14; }
    .ai-step-pending { color: #adb5bd; }
    .ai-step-active { color: #007a33; font-weight: 600; }
    .ai-step-done { color: #198754; }
    .ai-step-warning { color: #fd7e14; }
    .ai-events-box {
        max-height: 140px; overflow-y: auto;
        border: 1px solid #ffe0b2; border-radius: 10px;
        padding: 10px; background: #fff8e1;
    }
    .ai-event-error { color: #c62828; }
    .ai-event-fallback { color: #e65100; }
    .ai-event-info { color: #455a64; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .ai-workflow-step {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        background: #fff;
        min-width: 220px;
        flex: 1;
    }
    .ai-workflow-step.is-done {
        border-color: #a3cfbb;
        background: #f0fff4;
    }
    .ai-workflow-step.is-active {
        border-color: #9ec5fe;
        background: #e7f1ff;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }
    .ai-workflow-step.is-pending {
        opacity: 0.72;
    }
    .ai-workflow-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        background: #e9ecef;
        color: #495057;
        flex-shrink: 0;
    }
    .ai-workflow-step.is-done .ai-workflow-icon {
        background: #198754;
        color: #fff;
    }
    .ai-workflow-step.is-active .ai-workflow-icon {
        background: #0d6efd;
        color: #fff;
    }
    .ai-workflow-arrow {
        color: #adb5bd;
        font-size: 1.25rem;
        font-weight: 700;
    }
</style>

<script>
    const scaleCgpa = document.getElementById('scale_cgpa');
    const scalePercentage = document.getElementById('scale_percentage');
    const targetLabel = document.getElementById('target-label');
    const targetValue = document.getElementById('target_value');
    const targetSlider = document.getElementById('target_slider');
    const targetUnit = document.getElementById('target-unit');
    const scaleHints = document.getElementById('scale-hints');
    const targetPercentHidden = document.getElementById('target_percentage_hidden');

    const scales = {
        cgpa: { min: 2, max: 5, step: 0.01, label: 'Target CGPA on transcript', unit: 'CGPA',
            hints: ['2.0 Pass', '4.4 First Class', '5.0'],
            toPercent: (v) => Math.min(95, Math.max(45, 60 + (v / 5) * 20)) },
        percentage: { min: 45, max: 95, step: 0.5, label: 'Target percentage (all courses)', unit: '%',
            hints: ['45% Pass', '70% B', '80% A', '95%'],
            toPercent: (v) => v },
    };

    function currentScale() {
        return scalePercentage?.checked ? 'percentage' : 'cgpa';
    }

    function applyScale(scale, keepValue = true) {
        const cfg = scales[scale];
        targetLabel.textContent = cfg.label;
        targetUnit.textContent = cfg.unit;
        targetSlider.min = cfg.min;
        targetSlider.max = cfg.max;
        targetSlider.step = cfg.step;
        targetValue.min = cfg.min;
        targetValue.max = cfg.max;
        targetValue.step = cfg.step;
        scaleHints.innerHTML = cfg.hints.map(h => `<span>${h}</span>`).join('');
        if (!keepValue) {
            targetValue.value = scale === 'cgpa' ? '4.50' : '76.0';
        }
        syncFromInput();
    }

    function clamp(val, min, max) {
        return Math.min(max, Math.max(min, val));
    }

    function syncFromInput() {
        const scale = currentScale();
        const cfg = scales[scale];
        let val = parseFloat(targetValue.value);
        if (isNaN(val)) val = scale === 'cgpa' ? 4.5 : 76;
        val = clamp(val, cfg.min, cfg.max);
        targetValue.value = val.toFixed(scale === 'cgpa' ? 2 : 1);
        targetSlider.value = val;
        if (targetPercentHidden) {
            targetPercentHidden.value = cfg.toPercent(val).toFixed(1);
        }
    }

    function syncFromSlider() {
        targetValue.value = parseFloat(targetSlider.value).toFixed(currentScale() === 'cgpa' ? 2 : 1);
        syncFromInput();
    }

    if (scaleCgpa) scaleCgpa.addEventListener('change', () => applyScale('cgpa', false));
    if (scalePercentage) scalePercentage.addEventListener('change', () => applyScale('percentage', false));
    if (targetValue) targetValue.addEventListener('input', syncFromInput);
    if (targetValue) targetValue.addEventListener('change', syncFromInput);
    if (targetSlider) targetSlider.addEventListener('input', syncFromSlider);

    applyScale(@json($targetScale ?? 'cgpa'), true);

    const form = document.getElementById('ai-run-form');
    const overlay = document.getElementById('ai-overlay');
    const progressBar = document.getElementById('ai-progress-bar');
    const percentBadge = document.getElementById('ai-percent-badge');
    const statusText = document.getElementById('ai-status-text');
    const stepsList = document.getElementById('ai-steps-list');
    const eventsInner = document.getElementById('ai-events-inner');
    const doneActions = document.getElementById('ai-done-actions');
    const closeBtn = document.getElementById('ai-close-overlay');
    const runBtn = document.getElementById('ai-run-btn');
    const stopWrap = document.getElementById('ai-stop-wrap');
    const stopBtn = document.getElementById('ai-stop-btn');
    const transcriptPdfPanel = document.getElementById('transcript-pdf-panel');
    const transcriptPdfActions = document.getElementById('transcript-pdf-actions');
    const aiRunSummary = document.getElementById('ai-run-summary');
    const workflowStepAi = document.getElementById('workflow-step-ai');
    const workflowStepPdf = document.getElementById('workflow-step-pdf');
    const workflowAiHint = document.getElementById('workflow-ai-hint');
    const workflowPdfHint = document.getElementById('workflow-pdf-hint');
    const overlayPdfWrap = document.getElementById('ai-overlay-pdf-wrap');
    const overlayPdfBtn = document.getElementById('ai-overlay-pdf-btn');
    let activeCancelUrl = null;
    let stopRequested = false;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const profileReady = transcriptPdfPanel?.dataset.profileReady === '1';

    function setWorkflowStep(el, state) {
        if (!el) return;
        el.classList.remove('is-done', 'is-active', 'is-pending');
        el.classList.add(state);
    }

    function renderAiSummary(data) {
        if (!aiRunSummary) return;
        const parts = [
            '<span class="badge bg-success-subtle text-success me-1">AI done</span>',
            `${data.courses_processed || 0} courses`,
            `${data.materials_created || 0} PDFs`,
            `${data.questions_saved || 0} questions`,
            `${data.submissions_created || 0} marks`,
        ];
        if (data.achieved_cgpa) {
            parts.push(`CGPA ${Number(data.achieved_cgpa).toFixed(2)}`);
        }
        aiRunSummary.innerHTML = parts.join(' · ');
        aiRunSummary.classList.remove('text-muted');
    }

    function unlockTranscriptPdf(data) {
        if (transcriptPdfPanel) {
            transcriptPdfPanel.dataset.aiCompleted = '1';
        }

        setWorkflowStep(workflowStepAi, 'is-done');
        if (workflowAiHint) {
            workflowAiHint.textContent = 'Materials, quizzes & marks ready';
        }

        renderAiSummary(data || {});

        if (profileReady) {
            setWorkflowStep(workflowStepPdf, 'is-done');
            if (workflowPdfHint) {
                workflowPdfHint.textContent = 'Transcript ready to download';
            }
            if (transcriptPdfActions && transcriptPdfPanel) {
                const url = transcriptPdfPanel.dataset.transcriptUrl;
                transcriptPdfActions.innerHTML = `
                    <a href="${url}" target="_blank" class="btn btn-primary btn-sm w-100" id="transcript-pdf-btn">
                        <i class="ri-file-pdf-line"></i> Generate Transcript PDF
                    </a>`;
            }
            if (overlayPdfWrap && overlayPdfBtn && transcriptPdfPanel) {
                overlayPdfBtn.href = transcriptPdfPanel.dataset.transcriptUrl;
                overlayPdfWrap.classList.remove('d-none');
            }
        } else {
            setWorkflowStep(workflowStepPdf, 'is-pending');
            if (workflowPdfHint) {
                workflowPdfHint.textContent = 'Complete gender & date of birth';
            }
            if (transcriptPdfActions) {
                transcriptPdfActions.innerHTML = `
                    <button type="button" class="btn btn-primary btn-sm w-100" id="transcript-pdf-btn"
                            data-bs-toggle="modal" data-bs-target="#aiTranscriptProfileModal">
                        <i class="ri-file-pdf-line"></i> Complete profile &amp; generate PDF
                    </button>
                    <div class="small text-warning mt-2">Add gender and date of birth to unlock the PDF.</div>`;
            }
        }
    }

    function markAiRunning() {
        setWorkflowStep(workflowStepAi, 'is-active');
        if (workflowAiHint) {
            workflowAiHint.textContent = 'Running now…';
        }
        setWorkflowStep(workflowStepPdf, 'is-pending');
        if (workflowPdfHint) {
            workflowPdfHint.textContent = 'Unlocks after AI fill completes';
        }
    }

    const stepMarkers = {
        pending: '○',
        active: '…',
        done: '✓',
        warning: '!',
    };

    function renderSteps(steps) {
        if (!steps || !steps.length) return;

        stepsList.innerHTML = steps.map((step, index) => {
            const status = step.status || 'pending';
            const rowClass = `ai-step-item ai-step-item--${status}`;
            const iconClass = `ai-step-icon ai-step-icon--${status}`;
            const marker = stepMarkers[status] || stepMarkers.pending;
            const icon = status === 'active'
                ? '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                : marker;

            return `
            <div class="${rowClass}" data-step-id="${step.id}" data-step-status="${status}">
                <div class="${iconClass}" aria-label="${status}">${icon}</div>
                <div class="flex-grow-1">
                    <div class="small text-muted">Step ${index + 1}</div>
                    <div>${step.label}</div>
                </div>
            </div>`;
        }).join('');

        const activeEl = stepsList.querySelector('[data-step-status="active"]');
        if (activeEl) {
            activeEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function renderEvents(events) {
        if (!events || !events.length) {
            eventsInner.innerHTML = '<span class="text-muted">No API events yet.</span>';
            return;
        }
        eventsInner.innerHTML = events.slice().reverse().map(ev => {
            const cls = ev.type === 'error' ? 'ai-event-error' : (ev.type === 'fallback' ? 'ai-event-fallback' : 'ai-event-info');
            const fb = ev.fallback ? `<br><em>↳ Fallback: ${ev.fallback}</em>` : '';
            return `<div class="${cls} mb-2"><strong>${ev.time}</strong> — ${ev.message}${fb}</div>`;
        }).join('');
    }

    function pollProgress(url) {
        let interval;

        const finishRun = (data) => {
            clearInterval(interval);
            runBtn.disabled = false;
            stopWrap.classList.add('d-none');
            activeCancelUrl = null;
            doneActions.classList.remove('d-none');

            if (data.status === 'completed') {
                statusText.textContent = `Done! Achieved CGPA: ${data.achieved_cgpa ?? '—'}`;
                progressBar.classList.remove('progress-bar-animated');
                unlockTranscriptPdf(data);
            } else if (data.status === 'cancelled') {
                statusText.textContent = 'Stopped by user.';
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('bg-success');
                progressBar.classList.add('bg-warning');
                setWorkflowStep(workflowStepAi, 'is-pending');
                if (workflowAiHint) {
                    workflowAiHint.textContent = 'Run AI fill to continue';
                }
            } else {
                statusText.textContent = 'Failed: ' + (data.error_message || 'Unknown error');
                progressBar.classList.remove('bg-success');
                progressBar.classList.add('bg-danger');
                setWorkflowStep(workflowStepAi, 'is-pending');
                if (workflowAiHint) {
                    workflowAiHint.textContent = 'AI fill failed — try again';
                }
            }
        };

        const tick = async () => {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                const data = await res.json();

                if (data.cancel_url) {
                    activeCancelUrl = data.cancel_url;
                    stopWrap.classList.remove('d-none');
                }

                const pct = data.percent || 0;
                progressBar.style.width = pct + '%';
                percentBadge.textContent = pct + '%';
                renderSteps(data.steps);
                renderEvents(data.events);

                const active = (data.steps || []).find(s => s.status === 'active');
                const doneCount = data.completed_steps ?? (data.steps || []).filter(s => s.status === 'done').length;
                const totalCount = data.total_steps ?? (data.steps || []).length;

                if (data.status === 'cancelled') {
                    statusText.textContent = stopRequested ? 'Stopping…' : 'Stopped by user.';
                } else if (active) {
                    statusText.textContent = `Step ${doneCount + 1} of ${totalCount}: ${active.label}`;
                } else if (data.status === 'completed') {
                    statusText.textContent = 'Completed!';
                } else if (totalCount > 0) {
                    statusText.textContent = `Processing… ${doneCount}/${totalCount} steps done`;
                } else {
                    statusText.textContent = 'Processing…';
                }

                if (data.done) {
                    finishRun(data);
                }
            } catch (e) {
                statusText.textContent = 'Lost connection while polling progress…';
            }
        };

        tick();
        interval = setInterval(tick, 1000);
    }

    async function requestStopRun() {
        if (!activeCancelUrl || stopRequested) {
            return;
        }

        stopRequested = true;
        stopBtn.disabled = true;
        statusText.textContent = 'Stop requested — halting at next step…';

        try {
            await fetch(activeCancelUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
        } catch (e) {
            statusText.textContent = 'Could not send stop request. Use Stop in Recent AI runs table.';
            stopBtn.disabled = false;
            stopRequested = false;
        }
    }

    if (stopBtn) {
        stopBtn.addEventListener('click', requestStopRun);
    }

    if (closeBtn && overlay) {
        closeBtn.addEventListener('click', () => {
            overlay.classList.remove('active');
        });
    }

    if (form && overlay) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            overlay.classList.add('active');
            runBtn.disabled = true;
            doneActions.classList.add('d-none');
            overlayPdfWrap?.classList.add('d-none');
            stopWrap.classList.add('d-none');
            stopBtn.disabled = false;
            stopRequested = false;
            activeCancelUrl = null;
            progressBar.style.width = '0%';
            progressBar.classList.add('bg-success');
            progressBar.classList.remove('bg-danger');
            progressBar.classList.add('progress-bar-animated');
            statusText.textContent = 'Submitting AI run…';
            stepsList.innerHTML = '<p class="text-muted small mb-0 text-center py-2">Initializing…</p>';
            eventsInner.innerHTML = '<span class="text-muted">Waiting for API events…</span>';
            markAiRunning();

            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Failed to start AI run');
                }

                statusText.textContent = 'AI run started — tracking progress…';
                activeCancelUrl = data.cancel_url || null;
                if (activeCancelUrl) {
                    stopWrap.classList.remove('d-none');
                }
                pollProgress(data.poll_url);
            } catch (err) {
                statusText.textContent = 'Error: ' + err.message;
                runBtn.disabled = false;
                progressBar.classList.add('bg-danger');
            }
        });
    }

    @if (!empty($activeAiRun))
    if (overlay) {
        overlay.classList.add('active');
        if (runBtn) runBtn.disabled = true;
        markAiRunning();
        pollProgress(@json(route('ai-transcript-studio.run.progress', $activeAiRun)));
    }
    @endif
</script>
@endsection
