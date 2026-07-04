@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Schools List</h5>

                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">

                                <a href="{{ route('settings.schools.create') }}" class="btn btn-primary"><i
                                        class="ri-add-line align-bottom me-1"></i> Add
                                    School</a>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" style="width: 100%">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="name">Name</th>
                                <th class="sort" data-sort="program">Program</th>
                                <th class="sort" data-sort="status">Status</th>
                                <th class="sort" data-sort="degree-levels">Degree Levels</th>

                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="table-list">
                            @foreach ($schools as $school)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $school->name }}</td>
                                    <td>{{ $school->program->name }}</td>
                                    <td><span
                                            class="badge {{ $school->status == 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $school->status }}</span>
                                    </td>
                                    <td>
                                        <table>

                                            @foreach (App\Models\DegreeLevel::where('program_id', $school->program_id)->get() as $degreeLevel)
                                                <tr class="border-bottom mb-2">
                                                    <td style="width: 200px;">{{ $degreeLevel->name }}</td>
                                                    <td>
                                                        <em>Year 1 , Year 2</em>
                                                    </td>

                                                </tr>
                                            @endforeach
                                        </table>

                                    </td>

                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="#showModal" class="text-primary d-inline-block edit-btn">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal"
                                                    data-id="{{ $school->id }}" href="#deleteRecordModal">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach

                    </table>
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
                                            colors="primary:#f7b84b,secondary:#f06548"
                                            style="width:100px;height:100px"></lord-icon>
                                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                            <h4>Are you sure ?</h4>
                                            <p class="text-muted mx-4 mb-0">Are you sure you want to remove this record ?
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                        <button type="button" class="btn w-sm btn-light"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn w-sm btn-danger" id="delete-record">Yes, Delete
                                            It!</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end modal -->
            </div>
        </div>

    </div>
    <!--end col-->
    </div>
    <!--end row-->
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
            $(document).on('click', '#create-btn', function() {
                document.getElementById("exampleModalLabel").innerHTML = "Add School";
                var form_action = $(this).data('action');
                $('.tablelist-form').attr('action', form_action);
            });

            // update updateitem modl with clicked button data
            $(document).on('click', '.edit-btn', function() {

                document.getElementById("exampleModalLabel").innerHTML = "Edit School";
                document.getElementById("add-btn").innerHTML = "Save Changes";
                $('.tablelist-form').attr('action', $(this).data('action')).append(
                    '<input type="hidden" name="_method" value="PUT">');
                $('#id').val($(this).data('id'));
                $('#name').val($(this).data('name'));
                $('#program_id').val($(this).data('program_id'));
                $('#description').val($(this).data('description'));
                $('#status-field').val($(this).data('status'));

            });

            $(document).on('click', '.remove-item-btn', function() {
                var route = "{{ route('settings.schools.destroy', ['school' => ':id']) }}";
                route = route.replace(':id', $(this).data('id'));
                $('.delete-form').attr('action', route);
            });
        });
    </script>

    @if ($errors->any())
        @if (old('id'))
            <script>
                document.getElementById("add-btn").innerHTML = "Save Changes";
                document.getElementById("exampleModalLabel").innerHTML = "Edit Program";


                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()

                var id = "{{ old('id') }}";
                var route = "{{ route('settings.schools.update', ['school' => ':id']) }}";
                route = route.replace(':id', id);

                $('.tablelist-form').attr('action', route).append('<input type="hidden" name="_method" value="PUT">');
            </script>
        @else
            <script>
                document.getElementById("exampleModalLabel").innerHTML = "Add Program";

                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()
            </script>
        @endif
    @endif
@endsection
