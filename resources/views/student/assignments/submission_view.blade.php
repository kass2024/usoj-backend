@extends('layouts.student.app')

@section('css')
<style>
    .correct-answer {
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    .incorrect-answer {
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    .selected-answer {
        font-weight: bold;
    }
</style>
@endsection

@section('body')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5>Assignment Submission Details</h5>
        </div>
        <div class="card-body">
            @foreach ($submission->answers as $answerData)
                @php
                    $question = $submission->assignment->questions->firstWhere('id', $answerData['question_id']);
                @endphp
                <div class="card mb-3">
                    <div class="card-body">
                        <h6>Question {{ $loop->iteration }} ({{ $answerData['marks_obtained'] }} / {{ $question->marks }} Marks)</h6>
                        <p>{{ $question->title }}</p>

                        @switch($answerData['type'])
                            @case('radio')
                                @foreach ($question->choices as $choice)
                                    <div class="form-check {{
                                        $choice['id'] == $answerData['answer'] ? 'selected-answer' : ''
                                        }} {{
                                        $choice['is_correct'] ? 'correct-answer' : 'incorrect-answer'
                                    }}">
                                        <input type="radio" class="form-check-input" disabled
                                            {{ $choice['id'] == $answerData['answer'] ? 'checked' : '' }}>
                                        <label>{{ $choice['title'] }}</label>
                                    </div>
                                @endforeach
                                @break

                            @case('checkbox')
                                @foreach ($question->choices as $choice)
                                    <div class="form-check {{
                                        in_array($choice['id'], $answerData['answer'] ?? []) ? 'selected-answer' : ''
                                        }} {{
                                        $choice['is_correct'] ? 'correct-answer' : 'incorrect-answer'
                                    }}">
                                        <input type="checkbox" class="form-check-input" disabled
                                            {{ in_array($choice['id'], $answerData['answer'] ?? []) ? 'checked' : '' }}>
                                        <label>{{ $choice['title'] }}</label>
                                    </div>
                                @endforeach
                                @break

                            @case('open')
                                <textarea class="form-control" readonly>{{ $answerData['answer'] }}</textarea>
                                @break

                            @case('file')
                                @if(isset($answerData['file']))
                                    <a href="{{ Storage::url($answerData['file']) }}" target="_blank" class="btn btn-primary">View Submitted File</a>
                                @endif
                                @break
                        @endswitch
                    </div>
                </div>
            @endforeach

            <div class="card">
                <div class="card-body">
                    <h5>Total Marks: {{ $submission->marks_obtained }} / {{ $totalAssignmentMarks }}</h5>
                </div>
            </div>
    </div>
</div>
@endsection
