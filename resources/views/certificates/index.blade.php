@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="userList">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Generate student's document</h5>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('certificates.verify') }}" method="post">
                        @csrf
                        <label class="text-muted">Enter a registration number</label>
                        <div class="d-flex gap-3">
                            <input name="regNumber"
                                   type="text"
                                   class="form-control"
                                   value="{{ old('regNumber') }}"
                                   placeholder="e.g. 21MEE001">
                            <button class="btn btn-primary">check</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @isset($student)
        @php
            $photoUrl = \App\Support\StudentPhoto::url($student);
            $transcriptReady = \App\Support\TranscriptProfile::isReady($student);
            $missingProfile = \App\Support\TranscriptProfile::missingFields($student);
        @endphp

        <div class="card shadow mt-3">
            <div class="card-header bg-primary text-white">
                <h4 class="text-white mb-0">Student Information</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 text-center">
                        <img src="{{ $photoUrl }}"
                             class="img-fluid rounded border"
                             style="max-height:220px; object-fit:cover;"
                             alt="Student Photo">

                        <form action="{{ route('certificates.photo', encrypt($student->id)) }}"
                              method="post"
                              enctype="multipart/form-data"
                              class="mt-3 text-start">
                            @csrf
                            <label class="form-label small text-muted">Upload / change student photo</label>
                            <input type="file"
                                   name="profile_img"
                                   class="form-control form-control-sm mb-2"
                                   accept="image/jpeg,image/png,image/jpg"
                                   required>
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 mb-2">
                                Save Photo
                            </button>
                        </form>

                        @if ($student->profile_img)
                            <form action="{{ route('certificates.photo.delete', encrypt($student->id)) }}"
                                  method="post"
                                  onsubmit="return confirm('Remove this student photo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    Delete Photo
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="col-md-9">
                        <h5>{{ $student->fname }} {{ $student->lname }}</h5>
                        <p><strong>Registration Number:</strong> {{ $student->reg_number }}</p>
                        <p><strong>Phone:</strong> {{ $student->phone }}</p>
                        <p><strong>Status:</strong>
                            <span class="badge bg-success">{{ ucfirst($student->status) }}</span>
                        </p>
                        <p><strong>Gender:</strong> {{ \App\Support\TranscriptProfile::genderLabel($student) }}</p>
                        <p><strong>Date of Birth:</strong> {{ \App\Support\TranscriptProfile::dateOfBirthFormatted($student) }}</p>
                        <p><strong>Nationality:</strong> {{ \App\Support\TranscriptProfile::nationality($student) }}</p>
                        <p><strong>Completion Year:</strong> {{ \App\Support\TranscriptProfile::completionYear($student) }}
                            <span class="small text-muted">(auto)</span>
                        </p>
                        @if ($student->department)
                            <p><strong>Department:</strong>
                                {{ $student->department->name }} ({{ $student->department->abbr }})
                            </p>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
                    <span class="badge bg-secondary">Manual</span>
                    <span class="small text-muted">Uses marks already in the system — no AI changes.</span>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center mb-4">
                    @if ($transcriptReady)
                        <a target="_blank" href="{{ route('certificates.transcript', encrypt($student->id)) }}"
                           class="btn btn-primary" id="generate-transcript-btn">
                            <i class="ri-file-pdf-line"></i> Generate Transcript (Manual)
                        </a>
                    @else
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transcriptProfileModal">
                            <i class="ri-file-pdf-line"></i> Generate Transcript (Manual)
                        </button>
                        <span class="small text-warning">Missing: {{ \App\Support\TranscriptProfile::missingFieldsLabel($student) }}</span>
                    @endif

                    <a target="_blank" href="{{ route('certificates.degree', encrypt($student->id)) }}"
                       class="btn btn-success">
                        <i class="ri-award-line"></i> Generate Degree (Manual)
                    </a>

                    @if ($externalTranscript)
                        <a target="_blank"
                           href="{{ route('certificates.external.transcript', encrypt($student->id)) }}"
                           class="btn btn-outline-primary">
                            View Uploaded Transcript
                        </a>
                    @else
                        <button type="button" class="btn btn-outline-secondary" disabled title="No external transcript uploaded">
                            View Uploaded Transcript
                        </button>
                    @endif

                    @if ($externalDegree)
                        <a target="_blank"
                           href="{{ route('certificates.external.degree', encrypt($student->id)) }}"
                           class="btn btn-outline-success">
                            View Uploaded Degree
                        </a>
                    @else
                        <button type="button" class="btn btn-outline-secondary" disabled title="No external degree uploaded">
                            View Uploaded Degree
                        </button>
                    @endif
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
                        <span class="badge bg-warning text-dark">AI</span>
                        <span class="small text-muted">Auto-fills 4 years of marks via Gemini + bot. Clears old marks first.</span>
                    </div>
                    <form action="{{ route('ai-transcript-studio.lookup') }}" method="post" class="d-inline">
                        @csrf
                        <input type="hidden" name="reg_number" value="{{ $student->reg_number }}">
                        <button type="submit" class="btn btn-warning">
                            <i class="ri-robot-2-line"></i> Open AI Transcript Studio
                        </button>
                    </form>
                </div>

                <div class="border rounded p-3 bg-light mt-3">
                    <h6 class="mb-3">Email documents</h6>
                    <form action="{{ route('certificates.email', encrypt($student->id)) }}" method="post">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small text-muted">Recipient email</label>
                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       value="{{ old('email') }}"
                                       placeholder="name@example.com"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Document(s) to send</label>
                                <select name="documents" class="form-select" required>
                                    <option value="transcript" @selected(old('documents') === 'transcript')>Transcript</option>
                                    <option value="degree" @selected(old('documents') === 'degree')>Degree</option>
                                    <option value="both" @selected(old('documents', 'both') === 'both')>Both</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    Send Email
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="transcriptProfileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('certificates.transcript.profile', encrypt($student->id)) }}" id="transcript-profile-form">
                    @csrf
                    <input type="hidden" name="generate_after" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">Complete transcript profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Gender and date of birth are required on the official USJ transcript format.</p>
                        <div class="mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select gender</option>
                                <option value="MALE" @selected(old('gender', strtoupper((string) $student->gender)) === 'MALE')>Male</option>
                                <option value="FEMALE" @selected(old('gender', strtoupper((string) $student->gender)) === 'FEMALE')>Female</option>
                                <option value="OTHER" @selected(old('gender', strtoupper((string) $student->gender)) === 'OTHER')>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" max="{{ date('Y-m-d') }}"
                                   value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control"
                                   value="{{ old('nationality', $student->nationality) }}" placeholder="e.g. UGANDAN">
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
@endsection

@if (isset($student) && (session('transcript_profile_required') || $errors->has('gender') || $errors->has('date_of_birth')))
@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('transcriptProfileModal');
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });
</script>
@endsection
@endif
