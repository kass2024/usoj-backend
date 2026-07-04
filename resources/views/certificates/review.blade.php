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
                    <form action="{{ route('certificates.verify') }}" method="post">
                        @csrf
                        <label class="text-muted">Enter a registration number</label>
                        <div class="d-flex gap-3">
                            <input name="regNumber" type="text" class="form-control" value="{{ old('regNumber') }}">
                            <button class="btn btn-primary">check</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @isset($student)
        @if (session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        <div class="card shadow mt-3">
            <div class="card-header bg-primary text-white">
                <h4 class="text-white mb-0">Student Information</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 text-center">
                        <img src="{{ \App\Support\CertificatePresenter::photoUrl($student) }}"
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
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                Save Photo
                            </button>
                        </form>
                    </div>
                    <div class="col-md-9">
                        <h5>{{ $student->fname }} {{ $student->lname }}</h5>
                        <p><strong>Registration Number:</strong> {{ $student->reg_number }}</p>
                        <p><strong>Email:</strong> {{ $student->email }}</p>
                        <p><strong>Phone:</strong> {{ $student->phone }}</p>
                        <p><strong>Status:</strong>
                            <span class="badge bg-success">{{ ucfirst($student->status) }}</span>
                        </p>
                        <p><strong>Department:</strong>
                            {{ $student->department->name }} ({{ $student->department->abbr }})
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-3 flex-wrap">
                    <a target="_blank" href="{{ route('certificates.transcript', encrypt($student->id)) }}">
                        <button type="button" class="btn btn-primary">Generate Transcript</button>
                    </a>
                    <a target="_blank" href="{{ route('certificates.degree', encrypt($student->id)) }}">
                        <button type="button" class="btn btn-success">Generate Degree</button>
                    </a>
                </div>
            </div>
        </div>
    @endisset
@endsection
