@extends('layouts.app')
@section('body')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="userList">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Generate student's document</h5>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-body">
                    <!-- retrieve documents by student reg number -->
                    <form action="{{ route("certificates.verify") }}" method="post">
                        @csrf
                        <label for="" class="text-muted">Enter a registration number</label>
                        <div class="d-flex gap-3">
                            <input name="regNumber" type="text" class="form-control">
                            <button class="btn btn-primary">check</button>
                        </div>
                    </form>
                </div>


            </div>
        </div>

    </div>
    <!--end col-->
    </div>
    <!--end row-->
@endsection