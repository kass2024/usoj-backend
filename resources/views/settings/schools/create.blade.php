@extends('layouts.app')
@section('body')
    <form action="{{ route('settings.schools.store') }}" method="POST">
        @csrf
        <div class="row mt-4 justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <h4 for="create">CREATE SCHOOL</h4>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">School Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" id="name"
                                class="form-control" placeholder="Enter name" />
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="program" class="form-label">Program</label>
                                <select class="form-select" name="program_id" id="program">
                                    <option value="" selected disabled>Select</option>
                                    @foreach ($programs as $program)
                                        <option {{ old('program_id') == $program->id ? 'selected' : '' }}
                                            value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </select>
                                @error('program_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status-field" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status-field">
                                    <option value="" selected disabled>Select</option>
                                    <option {{ old('status') == 'active' ? 'selected' : '' }} value="active">
                                        Active</option>
                                    <option {{ old('status') == 'inactive' ? 'selected' : '' }} value="inactive">
                                        Inactive</option>
                                </select>
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <div class="table-responsive">
                            <table id="degree_levels_table" class="table">
                                <thead class="align-middle">
                                    <tr>
                                        <th>#</th>
                                        <th style="width: 280px;">Degree Lever</th>
                                        <th>Years</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>

                            </table>
                            <!--end table-->
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="4" class="form-control" placeholder="Enter description">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>

                        <div class="hstack gap-2 justify-content-end d-print-none mt-1">
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-bottom me-1"></i>
                                Save</button>
                        </div>
                    </div>

                </div>
                <!-- end card -->

            </div>
            <!-- end col -->
        </div>

        <!-- end row -->

    </form>
    <!--end col-->
    </div>
    <!--end row-->
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#program').change(function() {
                var programId = $(this).val();

                $.ajax({
                    url: "{{ route('settings.degree-levels.category', ':program_id') }}".replace(
                        ':program_id', programId),
                    type: 'GET',
                    success: function(data) {
                        $('#degree_levels_table tbody').empty();
                        $.each(data, function(index, degreeLevel) {
                            $('#degree_levels_table tbody').append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>
                                    <strong>${degreeLevel.name}</strong>
                                    <input type="hidden" value="${degreeLevel.id}" name="degreeLevels[]"/>
                                </td>
                                <td>
                                    <div class="input-step">
                                        <button type="button" class="minus">–</button>
                                        <input type="number" name="years[]" class="years" value="0" min="0" max="6" readonly>
                                        <button type="button" class="plus">+</button>
                                    </div>
                                </td>
                            </tr>`);
                        });

                        attachPlusMinusHandlers();
                    }
                });
            });

            function attachPlusMinusHandlers() {
                $('.minus').off('click').on('click', function() {
                    let input = $(this).siblings('input.years');
                    let currentValue = parseInt(input.val());
                    if (currentValue > parseInt(input.attr('min'))) {
                        input.val(currentValue - 1);
                    }
                });

                $('.plus').off('click').on('click', function() {
                    let input = $(this).siblings('input.years');
                    let currentValue = parseInt(input.val());
                    if (currentValue < parseInt(input.attr('max'))) {
                        input.val(currentValue + 1);
                    }
                });
            }

            attachPlusMinusHandlers();
        });
    </script>
@endsection
