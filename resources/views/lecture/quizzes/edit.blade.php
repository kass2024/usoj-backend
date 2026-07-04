@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-10">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Edit Question</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <a href="{{ route('lecture.quiz.questions', $question->quiz_id) }}" type="button" class="btn btn-primary">
                                    <i class="ri-arrow-left-line align-bottom me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" class="row g-3" action="{{ route('lecture.quiz.update_question', $question->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label>Question Type</label>
                            <select name="type" id="question-type" class="form-select" required>
                                <option value="" selected disabled>Select Question Type</option>
                                <option value="radio" {{ $question->type == 'radio' ? 'selected' : '' }}>Single Choice (Radio)</option>
                                <option value="checkbox" {{ $question->type == 'checkbox' ? 'selected' : '' }}>Multiple Choice (Checkbox)</option>
                                <option value="open" {{ $question->type == 'open' ? 'selected' : '' }}>Open Question</option>
                                <option value="file" {{ $question->type == 'file' ? 'selected' : '' }}>File Upload</option>
                            </select>
                            @error('type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="marks" class="form-label">Marks</label>
                            <input name="marks" type="number" min="1" max="100" required class="form-control"
                                placeholder="Marks" value="{{ old('marks', $question->marks) }}">
                            @error('marks')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Question</label>
                            <input name="title" type="text" required class="form-control" placeholder="Title"
                                value="{{ old('title', $question->title) }}">
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div id="choices-container" class="col-md-12 mb-2" style="{{ in_array($question->type, ['radio', 'checkbox']) ? '' : 'display: none;' }}">
                            <div class="row mb-2 g-3">
                                <table id="item_table" style="width: 100%">
                                    @php
                                        $oldChoices = old('choices') ?? ($question->choices ? collect($question->choices)->pluck('title', 'id')->toArray() : [1 => '']);
                                        $oldAnswers = old('answers') ?? ($question->choices ? collect($question->choices)->where('is_correct', true)->pluck('id')->toArray() : []);
                                    @endphp
                                    @foreach ($oldChoices as $key => $choice)
                                        <tr>
                                            <td>
                                                <div class="row mb-2 g-3">
                                                    <div class="col-1">
                                                        <input type="checkbox" value="{{ $key }}"
                                                            name="answers[{{ $key }}]" class="form-check-input"
                                                            style="width: 31px; height: 31px;"
                                                            {{ in_array($key, $oldAnswers) ? 'checked' : '' }}>
                                                    </div>
                                                    <div class="col-10">
                                                        <input name="choices[{{ $key }}]" type="text"
                                                            class="form-control" placeholder="Choice {{ $key }}"
                                                            value="{{ $choice }}">
                                                    </div>
                                                    <div class="col-1">
                                                        @if ($loop->first)
                                                            <button type="button" class="btn btn-primary add"><i
                                                                    class="ri-add-line"></i></button>
                                                        @else
                                                            <button type="button" class="btn btn-danger remove"><i
                                                                    class="ri-close-line"></i></button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                                @error('choices')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Update</button>
                            <button type="reset" class="btn btn-light">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Question type change handler
            $("#question-type").change(function() {
                var selectedType = $(this).val();

                // Hide choices container
                $("#choices-container").hide();

                // Reset previous state
                // $("#item_table input[type='checkbox']").prop('checked', false);
                // $("#item_table tr").not(":first").remove();

                // Dynamic validation handling
                switch (selectedType) {
                    case 'radio':
                    case 'checkbox':
                        $("#choices-container").show();
                        $("#item_table input[name^='choices']").prop('required', true);
                        break;
                    case 'open':
                    case 'file':
                        $("#item_table input[name^='choices']").prop('required', false);
                        break;
                }
            });

            // Trigger initial change
            $("#question-type").trigger('change');

            // Add choice functionality
            $(document).on("click", ".add", function() {
                var number_of_rows = $("#item_table tr").length + 1;

                var html = `<tr><td><div class="row mb-2 g-3">
            <div class="col-1">
                <input type="checkbox" value="${number_of_rows}"
                       name="answers[${number_of_rows}]"
                       class="form-check-input"
                       style="width: 31px; height: 31px;">
            </div>
            <div class="col-10">
                <input name="choices[${number_of_rows}]"
                       type="text"
                       class="form-control"
                       placeholder="Choice ${number_of_rows}">
            </div>
            <div class="col-1">
                <button class="btn btn-danger remove">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </div></td></tr>`;

                $("#item_table").append(html);
            });

            // Remove choice functionality
            $(document).on("click", ".remove", function() {
                $(this).closest("tr").remove();
            });
        });
    </script>
@endsection
