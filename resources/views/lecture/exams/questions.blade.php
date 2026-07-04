@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ $exam->title }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">

                                <a href="{{ route('lecture.exam.create', $exam->id) }}" type="button"
                                    class="btn btn-primary"><i class="ri-add-line align-bottom me-1"></i>
                                    Add Exam</a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        @foreach ($questions as $question)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">Q.{{ $loop->iteration }}) {{ $question->title }}</h5>
                </div>
                @if (in_array($question->type, ['radio', 'checkbox']))
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach ($question->choices as $choice)
                                <li class="list-group-item {{ $choice['is_correct'] ? 'list-group-item-success' : '' }}">
                                    {{ $loop->iteration }}. {{ $choice['title'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span><strong>Marks:</strong> {{ $question->marks }}</span>
                    <div>
                        <!-- Edit Button -->
                        <a href="{{ route('lecture.exam.edit', $question->id) }}" class="btn btn-sm btn-warning">
                            <i class="ri-edit-line"></i> Edit
                        </a>

                        <!-- Delete Button -->
                        <form action="{{ route('lecture.exam.delete_question', $question->id) }}" method="POST"
                            style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Are you sure you want to delete this question?')">
                                <i class="ri-delete-bin-line"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
