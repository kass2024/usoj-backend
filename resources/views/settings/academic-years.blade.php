@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-6">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Academics year List</h5>

                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">

                                <button type="button" class="btn btn-primary add-btn"
                                    data-action="{{ route('settings.academic-years.store') }}" data-bs-toggle="modal"
                                    id="create-btn" data-bs-target="#showModal"><i
                                        class="ri-add-line align-bottom me-1"></i> Add
                                    New</button>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" id="ProgramTable" style="width: 100%">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="name">Period</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="table-list">
                            @foreach ($academics as $academic)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $academic->period }}</td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="#showModal" data-bs-toggle="modal" data-id="{{ $academic->id }}"
                                                    data-period="{{ $academic->period }}"
                                                    data-action="{{ route('settings.academic-years.update', ['academic_year' => $academic->id]) }}"
                                                    class="text-primary d-inline-block edit-btn">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal"
                                                    data-id="{{ $academic->id }}" href="#deleteRecordModal">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach

                    </table>
                </div>


                <div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-light p-3">
                                <h5 class="modal-title" id="exampleModalLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                    id="close-modal"></button>
                            </div>
                            <form class="modal-form" method="POST" autocomplete="off">
                                @csrf

                                <div class="modal-body">

                                    <input type="hidden" value="{{ old('id') }}" name="id" id="id" />

                                    <div class="mb-3">
                                        <label for="period" class="form-label">Period</label>
                                        <input type="text" name="period" value="{{ old('period') }}" id="period"
                                            class="form-control" placeholder="2020-2021" />
                                        @error('period')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" id="add-btn">Submit</button>
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
                document.getElementById("exampleModalLabel").innerHTML = "Add Academic year";
                var form_action = $(this).data('action');
                $('.modal-form').attr('action', form_action);
            });

            // update updateitem modl with clicked button data
            $(document).on('click', '.edit-btn', function() {

                document.getElementById("exampleModalLabel").innerHTML = "Edit Academic year";
                document.getElementById("add-btn").innerHTML = "Save Changes";
                $('.modal-form').attr('action', $(this).data('action')).append(
                    '<input type="hidden" name="_method" value="PUT">');
                $('#id').val($(this).data('id'));
                $('#period').val($(this).data('period'));

            });

            $(document).on('click', '.remove-item-btn', function() {
                var route = "{{ route('settings.academic-years.destroy', ['academic_year' => ':id']) }}";
                route = route.replace(':id', $(this).data('id'));
                $('.delete-form').attr('action', route);
            });
        });
    </script>

    @if ($errors->any())
        @if (old('id'))
            <script>
                document.getElementById("add-btn").innerHTML = "Save Changes";
                document.getElementById("exampleModalLabel").innerHTML = "Edit Academic year";


                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()

                var id = "{{ old('id') }}";
                var route = "{{ route('settings.academic-years.update', ['academic_year' => ':id']) }}";
                route = route.replace(':id', id);

                $('.modal-form').attr('action', route).append('<input type="hidden" name="_method" value="PUT">');
            </script>
        @else
            <script>
                document.getElementById("exampleModalLabel").innerHTML = "Add Academic year";

                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()
            </script>
        @endif
    @endif
@endsection
