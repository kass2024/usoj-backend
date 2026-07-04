@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Students List</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <button data-bs-toggle="modal" data-bs-target="#showModal" class="btn btn-primary add-btn">
                                    <i class="ri-add-line align-bottom me-1"></i> Add Student
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" id="students" style="width: 100%">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="reg_no">Reg N<sup>0</sup></th>
                                <th class="sort" data-sort="name">Name</th>
                                <th class="sort" data-sort="email">Email</th>
                                <th class="sort" data-sort="phone">Phone</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="table-list">
                            @foreach ($students_in_class as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->student->reg_number }}</td>
                                    <td>{{ $item->student->fname }} {{ $item->student->lname }}</td>
                                    <td>{{ $item->student->email }}</td>
                                    <td>{{ $item->student->phone }}</td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal"
                                                    data-id="{{ $item->id }}" href="#deleteRecordModal">
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


                <div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-light p-3">
                                <h5 class="modal-title">Add Students in class</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                    id="close-modal"></button>
                            </div>
                            <form method="POST" action="{{ route('hod.students.store', $year) }}" autocomplete="off">
                                @csrf

                                <div class="modal-body">

                                    <div class="mb-3">
                                        <label for="students" class="form-label">Students</label>
                                        <select multiple class="form-select select2" data-placeholder="Select Students"
                                            name="students[]" id="students">
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}">{{ $student->fname }}
                                                    {{ $student->lname }} ({{ $student->reg_number }})</option>
                                            @endforeach

                                        </select>
                                        @error('students')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" id="add-btn">Add Students</button>
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
    <link href="/assets/css/select2.min.css" rel="stylesheet" />
    @include('layouts.datatable.css-without-bottons')
    {{-- @include('layouts.datatable.css-with-bottons') --}}
@endsection
@section('js')
    <!--datatable js-->
    @include('layouts.datatable.js-without-bottons')
    {{-- @include('layouts.datatable.js-with-bottons') --}}
    <script src="/assets/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".select2").select2()
            $('#students').DataTable()
        });
    </script>

    <script>
        $(document).ready(function() {

            $(document).on('click', '.remove-item-btn', function() {
                var route = "{{ route('hod.students.destroy', ['id' => ':id']) }}";
                route = route.replace(':id', $(this).data('id'));
                $('.delete-form').attr('action', route);
            });
        });
    </script>

    @if ($errors->any())
        <script>
            var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                keyboard: false
            })
            myModal.show()
        </script>
    @endif
@endsection
