@extends('layouts.app')
@section('body')
    <div class="row">

        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ $module->course->name }}'s Assignments</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">

                                <button type="button" class="btn btn-primary" id="create-assignment" data-bs-toggle="modal"
                                    data-bs-target="#addAssignmentModal"
                                    data-action="{{ route('lecture.assignment.store', $module->id) }}"><i
                                        class="ri-add-line align-bottom me-1"></i> Add Assignment</button>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" id="modules" style="width: 100%">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="title">Title</th>
                                <th class="sort" data-sort="due_date">Due Date</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @foreach ($assignments as $assignment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $assignment->title }}</td>
                                    <td>{{ $assignment->due_date }}</td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="{{ route('lecture.assignment.questions', $assignment->id) }}"
                                                    class="text-primary d-inline-block text-decoration-underline">
                                                    Questions
                                                </a>
                                            </li>
                                            <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="#addAssignmentModal" data-bs-toggle="modal"
                                                    data-id="{{ $assignment->id }}" data-title="{{ $assignment->title }}"
                                                    data-due_date="{{ $assignment->due_date }}"
                                                    data-action="{{ route('lecture.assignment.update', ['id' => $assignment->id]) }}"
                                                    class="text-primary d-inline-block edit-assignment-btn">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal"
                                                    data-action="{{ route('lecture.assignment.delete', ['id' => $assignment->id]) }}"
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


    <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="assignmentModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="close-modal"></button>
                </div>
                <form class="assignment-modal-form" method="POST" autocomplete="off">
                    @csrf

                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="assignment_title" class="form-label">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}" id="assignment_title"
                                class="form-control" placeholder="Enter a title" />
                            @error('title')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="datetime-local" name="due_date" value="{{ old('due_date') }}" id="due_date"
                                class="form-control" placeholder="Provide due date" />
                            @error('due_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="add-assignment-btn">Submit</button>
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
            $('#modules').DataTable()
        });
    </script>
    <script>
        $(document).ready(function() {

            $(document).on('click', '#create-assignment', function() {
                document.getElementById("assignmentModalTitle").innerHTML = "Add New Assignment";
                var form_action = $(this).data('action');
                $('.assignment-modal-form').attr('action', form_action);
            });

            $(document).on('click', '.edit-assignment-btn', function() {

                document.getElementById("assignmentModalTitle").innerHTML = "Edit Assignment";;
                document.getElementById("add-assignment-btn").innerHTML = "Save Changes";
                $('.assignment-modal-form').attr('action', $(this).data('action')).append(
                    '<input type="hidden" name="_method" value="PUT">');
                $('#id').val($(this).data('id'));
                $('#assignment_title').val($(this).data('title'));
                $('#due_date').val($(this).data('due_date'));

            });

            $(document).on('click', '.remove-item-btn', function() {
                $('.delete-form').attr('action', $(this).data('action'));
            });
        });
    </script>

    @if ($errors->any())
        @if (old('id'))
            <script>
                document.getElementById("assignmentModalTitle").innerHTML = "Edit Assignment";;
                document.getElementById("add-assignment-btn").innerHTML = "Save Changes";

                var myModal = new bootstrap.Modal(document.getElementById('addAssignmentModal'), {
                    keyboard: false
                })
                myModal.show()

                var id = "{{ old('id') }}";
                var route = "{{ route('lecture.assignment.update', ['id' => ':id']) }}";
                route = route.replace(':id', id);

                $('.assignment-modal-form').attr('action', route).append('<input type="hidden" name="_method" value="PUT">');
            </script>
        @else
            <script>
                document.getElementById("lessonModalTitle").innerHTML = "Add Assignment";

                var id = "{{ $module->id }}";
                var route = "{{ route('lecture.assignment.store', ['id' => ':id']) }}";
                route = route.replace(':id', id);

                $('.assignment-modal-form').attr('action', route)
                var myModal = new bootstrap.Modal(document.getElementById('addAssignmentModal'), {
                    keyboard: false
                })
                myModal.show()
            </script>
        @endif
    @endif
@endsection
