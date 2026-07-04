@extends('layouts.student.app')
@section('body')
    <div class="row">
        <div class="col-md-6">
            <div class="card" id="List">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ $module->course->name }}'s Lessons</h5>
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
                                <th class="sort" data-sort="document">Document</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @foreach ($lessons as $lesson)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $lesson->title }}</td>
                                    <td><a href="/storage/app/public/{{ $lesson->document }}" target="_blank"
                                            rel="noopener noreferrer">File</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
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
        // $(document).ready(function() {
        //     $('#modules').DataTable()
        // });
    </script>
@endsection
