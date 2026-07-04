@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="userList">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Generate student's document</h5>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-body">
                    <!-- retrieve documents by student reg number -->
                    <form action="{{ route("certificates.verify") }}" method="post">
                        @csrf
                        <label for="" class="text-muted">Enter a registration number</label>
                        <div class="d-flex gap-3">
                            <input name="regNumber" type="text" class="form-control">
                            <button class="btn btn-primary">check</button>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    </div>


    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="text-white">Student Information</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    
    <img src="{{ asset('images/profile.jpg') }}" class="img-fluid rounded" alt="Profile Image">


                </div>
                <div class="col-md-9">
                    <h5>{{ $student->fname }} {{ $student->lname }}</h5>
                    <p><strong>Registration Number:</strong> {{ $student->reg_number }}</p>
                    <p><strong>Email:</strong> {{ $student->email }}</p>
                    <p><strong>Phone:</strong> {{ $student->phone }}</p>
                    <p><strong>Status:</strong> <span class="badge bg-success">{{ ucfirst($student->status) }}</span>
                    </p>
                    <p><strong>Department:</strong> {{ $student->department->name }} ({{ $student->department->abbr }})
                    </p>
                </div>
            </div>
            <div class="container">
                <div class="row mb-3">
                    <!-- generate buttons -->
                    <div class="d-flex gap-3">
                        <a target="_blank" href="{{ route("certificates.transcript", encrypt($student->id)) }}">
                            <button class="btn btn-primary">Generate Transcript</button>
                        </a>

                        <a target="_blank" href="{{ route("certificates.degree", encrypt($student->id)) }}">
                            <button class="btn btn-success">Generate Degree</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection