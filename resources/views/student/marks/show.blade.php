@extends('layouts.student.app')

@section('body')
<div class="container py-4">
    <h4 class="mb-3">{{ $submission->exam->title }} - Details</h4>

    <div class="card">
        <div class="card-body">
            <p><strong>Marks Obtained:</strong> {{ $submission->marks_obtained }} / {{ $submission->exam->total_marks }}</p>
            <p><strong>Date:</strong> {{ $submission->created_at->format('d M Y, H:i') }}</p>
            <p><strong>Status:</strong> {{ $submission->status ?? 'Completed' }}</p>
        </div>
    </div>

    <a href="{{ route('student.marks.index') }}" class="btn btn-secondary mt-3">Back to Marks</a>
</div>
@endsection
