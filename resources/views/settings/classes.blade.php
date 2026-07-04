@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Classes List</h5>

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
                                <th class="sort" data-sort="degree-levels">Degree Levels</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="table-list">
                            @foreach ($departments as $department)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $department->name }}</td>
                                    <td>
                                        <table>

                                            @foreach (App\Models\DegreeLevel::where('program_id', $department->school->program_id)->get() as $degreeLevel)
                                                <tr class="border-bottom mb-2">
                                                    <td style="width: 200px;">{{ $degreeLevel->name }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div id="years-container-{{ $degreeLevel->id }}"
                                                                class="d-flex flex-wrap gap-2">
                                                                @foreach ($degreeLevel->classesForDepartmemt($department->id)->get() as $class)
                                                                    <span
                                                                        class="badge bg-primary d-flex align-items-center me-1">
                                                                        {{ $class->year_name }} ({{ $class->semester }})
                                                                        <button type="button"
                                                                            class="btn-close btn-close-white ms-2"
                                                                            onclick="removeYear({{ $class->id }})"
                                                                            style="font-size: 0.6rem;"></button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-warning ms-2"
                                                                            onclick="editYear({{ $class }})"
                                                                            style="font-size: 0.6rem;"><i
                                                                                class="ri-edit-line align-bottom"></i></button>
                                                                    </span>
                                                                @endforeach

                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-primary ms-2"
                                                                onclick="addYear({{ $degreeLevel->id }},{{ $department->id }})"
                                                                style="font-size: 0.5rem;">
                                                                <i class="ri-add-line align-bottom"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>

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
                                    <input type="hidden" name="degree_level_id" id="degree_level_id">
                                    <input type="hidden" name="department_id" id="department_id">

                                    <div class="mb-3">
                                        <label for="year_name" class="form-label">Year Name</label>
                                        <input type="text" name="year_name" value="{{ old('year_name') }}"
                                            id="year_name" class="form-control" placeholder="Year name" required />
                                        @error('year_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="semester" class="form-label">Semester</label>
                                        <input type="number" min="1" max="5" name="semester"
                                            value="{{ old('semester') }}" id="semester" class="form-control"
                                            placeholder="Semester" required />
                                        @error('semester')
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
        function addYear(degreeLevel, department) {
            document.getElementById("exampleModalLabel").innerHTML = "Add Year";
            const addModal = new bootstrap.Modal(document.getElementById('showModal'));
            addModal.show();
            $('#degree_level_id').val(degreeLevel);
            $('#department_id').val(department);
            var form_action = "{{ route('settings.classes.store') }}";
            $('.modal-form').attr('action', form_action);

        }

        function editYear(classData) {

            document.getElementById("exampleModalLabel").innerHTML = "Edit Year";
            document.getElementById("add-btn").innerHTML = "Save Changes";

            var route = "{{ route('settings.classes.update', ['class' => ':id']) }}";
            route = route.replace(':id', classData.id);

            $('.modal-form').attr('action', route).append(
                '<input type="hidden" name="_method" value="PUT">');
            $('#id').val(classData.id);
            $('#year_name').val(classData.year_name);
            $('#semester').val(classData.semester);

            const addModal = new bootstrap.Modal(document.getElementById('showModal'));
            addModal.show();

        }

        function removeYear(classId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));
            deleteModal.show();

            var route = "{{ route('settings.classes.destroy', ['class' => ':id']) }}";
            route = route.replace(':id', classId);
            $('.delete-form').attr('action', route);

        }
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
                var route = "{{ route('settings.classes.update', ['class' => ':id']) }}";
                route = route.replace(':id', id);

                $('.modal-form').attr('action', route).append('<input type="hidden" name="_method" value="PUT">');
            </script>
        @else
            <script>
                document.getElementById("exampleModalLabel").innerHTML = "Add Program";
                $('#degree_level_id').val("{{ old('degree_level_id') }}");
                $('#department_id').val("{{ old('department_id') }}");
                var myModal = new bootstrap.Modal(document.getElementById('showModal'), {
                    keyboard: false
                })
                myModal.show()
            </script>
        @endif
    @endif
@endsection
