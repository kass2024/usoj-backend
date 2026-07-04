@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Modules List</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table align-middle" id="modules" style="width: 100%">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col" style="width: 20px;">#</th>
                                <th class="sort" data-sort="code">Code</th>
                                <th class="sort" data-sort="name">Name</th>
                                <th class="sort" data-sort="semester">Semester</th>
                                <th class="sort" data-sort="credits">Credits</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @foreach ($modules as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->course->code }}</td>
                                    <td>{{ $item->course->name }}</td>
                                    <td>{{ $item->semester }}</td>
                                    <td>{{ $item->course->credits }}</td>

                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">

                                            <li class="list-inline-item">
                                                <div class="dropdown">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" style="">
                                                        <li><a class="dropdown-item view-item-btn"
                                                                href="{{ route('lecture.module', $item->id) }}">Lessons</a>
                                                        </li>
                                                        <li><a class="dropdown-item view-item-btn"
                                                                href="{{ route('lecture.quizzes', $item->id) }}">Quizzes</a>
                                                        </li>
                                                        <li><a class="dropdown-item view-item-btn"
                                                                href="{{ route('lecture.assignments', $item->id) }}">Assignments</a>
                                                        </li>
                                                        <li><a class="dropdown-item view-item-btn"
                                                                href="{{ route('lecture.exams', $item->id) }}">Exams</a>
                                                        </li>

                                                    </ul>
                                                </div>
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
        <div class="col-md-12">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Students List</h5>
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
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @foreach ($students as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->student->reg_number }}</td>
                                    <td>{{ $item->student->fname }} {{ $item->student->lname }}</td>
                                    <td>{{ $item->student->email }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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
            $('#students').DataTable()
            $('#modules').DataTable()
        });
    </script>
@endsection
