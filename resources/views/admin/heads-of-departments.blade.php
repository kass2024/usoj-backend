@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="userList">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Heads of Department</h5>

                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">

                                <button type="button" class="btn btn-primary add-btn"
                                    data-action="{{ route('users.store') }}" data-bs-toggle="modal" id="create-btn"
                                    data-bs-target="#showModal"><i class="ri-add-line align-bottom me-1"></i> Add
                                    Leader</button>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" id="usersTable" style="width: 100%;">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="department">Department</th>
                                <th class="sort" data-sort="name">Name</th>
                                <th class="sort" data-sort="email">Email</th>
                                <th class="sort" data-sort="phone">Phone</th>
                                <th class="sort" data-sort="status">Status</th>
                                <th class="sort" data-sort="date">Joining Date</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>

                    </table>
                </div>


                <div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-light p-3">
                                <h5 class="modal-title" id="exampleModalLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                    id="close-modal"></button>
                            </div>
                            <form class="tablelist-form" method="POST" autocomplete="off">
                                @csrf

                                <div class="modal-body">

                                    <input type="hidden" value="{{ old('id') }}" name="id" id="id" />
                                    <input type="hidden" value="head_of_department" name="role" />

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" name="name" value="{{ old('name') }}" id="name"
                                            class="form-control" placeholder="Enter name" />
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" value="{{ old('email') }}" id="email"
                                            class="form-control" placeholder="Enter email" />
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" name="phone" value="{{ old('phone') }}" id="phone"
                                            class="form-control" placeholder="Enter phone no." />
                                        @error('phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="row mb-3">

                                        <div class="col-md-6">
                                            <label for="department_id" class="form-label">Department</label>
                                            <select class="form-select" name="department_id" id="department_id">
                                                <option value="" selected disabled>Select</option>
                                                @foreach ($departments as $department)
                                                    <option {{ old('department_id') == $department->id ? 'selected' : '' }}
                                                        value="{{ $department->id }}">{{ $department->name }}</option>
                                                @endforeach

                                            </select>
                                            @error('status')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="status-field" class="form-label">Status</label>
                                            <select class="form-select" name="status" id="status-field">
                                                <option value="" selected disabled>Select</option>
                                                <option {{ old('status') == 'active' ? 'selected' : '' }} value="active">
                                                    Active</option>
                                                <option {{ old('status') == 'block' ? 'selected' : '' }} value="block">
                                                    Block</option>
                                            </select>
                                            @error('status')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" id="add-btn">Add
                                            HoD</button>
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
            var status = {
                active: {
                    title: "Active",
                    class: "badge bg-success-subtle text-success text-uppercase"
                },
                block: {
                    title: "Block",
                    class: "badge bg-danger-subtle text-danger text-uppercase"
                },
            };

            $('#usersTable').DataTable({

                ajax: {
                    url: "{{ route('users.heads_of_departments_all') }}",
                    dataSrc: 'data'
                },
                scrollX: true,
                columns: [{
                        data: null, // Use null data source
                        render: function(data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    {
                        data: 'department'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'phone'
                    },
                    {
                        data: null,
                        render: function(data, type, row, meta) {
                            return `<span class="${status[row.status].class}">${status[row.status].title}</span>`;
                        }
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: null, // Use null data source
                        render: function(data, type, row, meta) {
                            var route = "{{ route('users.update', ['user' => ':id']) }}";
                            route = route.replace(':id', row.id);
                            return `<ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="#showModal" data-bs-toggle="modal" data-id="${row.id }"
                                                    data-name="${row.name }" data-email="${row.email }"
                                                    data-phone="${row.phone }" data-status="${row.status }"
                                                    data-action="${route }" data-department_id="${row.department_id}"
                                                    class="text-primary d-inline-block edit-btn">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal" data-id="${row.id }"
                                                    href="#deleteRecordModal">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>`;
                        }
                    },

                ]
            });
        });



        // check if data loaded fully
        $(document).ready(function() {
            $(document).on('click', '#create-btn', function() {
                document.getElementById("exampleModalLabel").innerHTML = "Add HoD";
                var form_action = $(this).data('action');
                $('.tablelist-form').attr('action', form_action);
            });

            // update updateitem modl with clicked button data
            $(document).on('click', '.edit-btn', function() {

                document.getElementById("exampleModalLabel").innerHTML = "Edit HoD";
                document.getElementById("add-btn").innerHTML = "Save Changes";
                $('.tablelist-form').attr('action', $(this).data('action')).append(
                    '<input type="hidden" name="_method" value="PUT">');
                $('#id').val($(this).data('id'));
                $('#name').val($(this).data('name'));
                $('#email').val($(this).data('email'));
                $('#phone').val($(this).data('phone'));
                $('#department_id').val($(this).data('department_id'));
                $('#status-field').val($(this).data('status'));

            });

            $(document).on('click', '.remove-item-btn', function() {
                var route = "{{ route('users.destroy', ['user' => ':id']) }}";
                route = route.replace(':id', $(this).data('id'));
                $('.delete-form').attr('action', route);
            });
        });
    </script>

    @if ($errors->any())
        @if (old('id'))
            <script>
                document.getElementById("add-btn").innerHTML = "Save Changes";
                document.getElementById("exampleModalLabel").innerHTML = "Edit HoD";


                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()

                var id = "{{ old('id') }}";
                var route = "{{ route('users.update', ['user' => ':id']) }}";
                route = route.replace(':id', id);

                $('.tablelist-form').attr('action', route).append('<input type="hidden" name="_method" value="PUT">');
            </script>
        @else
            <script>
                document.getElementById("exampleModalLabel").innerHTML = "Add HoD";

                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()
            </script>
        @endif
    @endif
@endsection
