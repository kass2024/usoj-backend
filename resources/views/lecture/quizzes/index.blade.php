@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ $module->course->name }}'s Quizzes</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">

                                <button type="button" class="btn btn-primary" id="create-quiz" data-bs-toggle="modal"
                                    data-bs-target="#addQuizModal"
                                    data-action="{{ route('lecture.quiz.store', $module->id) }}"><i
                                        class="ri-add-line align-bottom me-1"></i> Add Quiz</button>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" id="datatable" style="width: 100%">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="title">Title</th>
                                <th class="sort" data-sort="start">Start Date</th>
                                <th class="sort" data-sort="end">End At</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @foreach ($quizzes as $quiz)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $quiz->title }}</td>
                                    <td>{{ $quiz->start_date }}</td>
                                    <td>{{ $quiz->end_date }}</td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="{{ route('lecture.quiz.questions', $quiz->id) }}"
                                                    class="text-primary d-inline-block text-decoration-underline">
                                                    Questions
                                                </a>
                                            </li>
                                            <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="#addQuizModal" data-bs-toggle="modal" data-id="{{ $quiz->id }}"
                                                    data-title="{{ $quiz->title }}"
                                                    data-start_date="{{ $quiz->start_date }}"
                                                    data-end_date="{{ $quiz->end_date }}"
                                                    data-action="{{ route('lecture.quiz.update', ['id' => $quiz->id]) }}"
                                                    class="text-primary d-inline-block edit-quiz-btn">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal"
                                                    data-action="{{ route('lecture.quiz.delete', ['id' => $quiz->id]) }}"
                                                    href="#deleteRecordModal">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="addQuizModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="quizModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="close-modal"></button>
                </div>
                <form class="quiz-modal-form" method="POST" autocomplete="off">
                    @csrf

                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="quiz_title" class="form-label">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}" id="quiz_title"
                                class="form-control" placeholder="Enter a title" />
                            @error('title')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="datetime-local" name="start_date" value="{{ old('start_date') }}" id="start_date"
                                class="form-control" placeholder="Provide start date" />
                            @error('start_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Start Date</label>
                            <input type="datetime-local" name="end_date" value="{{ old('end_date') }}" id="end_date"
                                class="form-control" placeholder="Provide start date" />
                            @error('end_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="add-quiz-btn">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal"
                        aria-label="Close" id="btn-close"></button>
                </div>
                <div class="modal-body">
                    <form class="delete-form" method="post">
                        @csrf
                        @method('DELETE')
                        <div class="mt-2 text-center">
                            <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                            <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                <h4>Are you sure ?</h4>
                                <p class="text-muted mx-4 mb-0">Are you sure you want to remove this record ?
                                </p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                            <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn w-sm btn-danger" id="delete-record">Yes, Delete
                                It!</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end modal -->
@endsection
@section('css')
    @include('layouts.datatable.css-without-bottons')
    {{-- @include('layouts.datatable.css-with-bottons') --}}
@endsection
@section('js')
    <!--datatable js-->
    @include('layouts.datatable.js-without-bottons')
    {{-- @include('layouts.datatable.js-with-bottons') --}}
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable()
        });
    </script>
    <script>
        $(document).ready(function() {

            $(document).on('click', '#create-quiz', function() {
                document.getElementById("quizModalTitle").innerHTML = "Add New Quiz";
                var form_action = $(this).data('action');
                $('.quiz-modal-form').attr('action', form_action);
            });

            $(document).on('click', '.edit-quiz-btn', function() {

                document.getElementById("quizModalTitle").innerHTML = "Edit Quiz";;
                document.getElementById("add-quiz-btn").innerHTML = "Save Changes";
                $('.quiz-modal-form').attr('action', $(this).data('action')).append(
                    '<input type="hidden" name="_method" value="PUT">');
                $('#id').val($(this).data('id'));
                $('#quiz_title').val($(this).data('title'));
                $('#start_date').val($(this).data('start_date'));
                $('#end_date').val($(this).data('end_date'));

            });


            $(document).on('click', '.remove-item-btn', function() {
                $('.delete-form').attr('action', $(this).data('action'));
            });
        });
    </script>

    @if ($errors->any())
        @if (old('id'))
            <script>
                document.getElementById("quizModalTitle").innerHTML = "Edit Quiz";;
                document.getElementById("add-quiz-btn").innerHTML = "Save Changes";

                var myModal = new bootstrap.Modal(document.getElementById('addQuizModal'), {
                    keyboard: false
                })
                myModal.show()

                var id = "{{ old('id') }}";
                var route = "{{ route('lecture.quiz.update', ['id' => ':id']) }}";
                route = route.replace(':id', id);

                $('.quiz-modal-form').attr('action', route).append('<input type="hidden" name="_method" value="PUT">');
            </script>
        @else
            <script>
                document.getElementById("lessonModalTitle").innerHTML = "Add Lesson";

                var id = "{{ $module->id }}";
                var route = "{{ route('lecture.quiz.store', ['id' => ':id']) }}";
                route = route.replace(':id', id);

                $('.quiz-modal-form').attr('action', route)
                var myModal = new bootstrap.Modal(document.getElementById('addQuizModal'), {
                    keyboard: false
                })
                myModal.show()
            </script>
        @endif
    @endif
@endsection
