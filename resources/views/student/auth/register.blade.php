@extends('layouts.student.guest')
@section('body')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card overflow-hidden">
                    <div class="row g-0">
                        <div class="col-lg-5">
                            <div class="p-lg-5 p-4 auth-one-bg h-100">
                                <div class="bg-overlay"></div>
                                <div class="position-relative h-100 d-flex flex-column">
                                    <div class="mb-4">

                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end col -->

                        <div class="col-lg-7">
                            <div class="p-lg-5 p-4">
                                <div>
                                    <h5 class="text-primary">Register Account</h5>
                                    <p class="text-muted">Create your USJ student account.</p>
                                </div>
                                <x-auth-session-status class="mb-4" :status="session('status')" />


                                <div class="mt-4">
                                    <form id="registrationForm" method="POST">
                                        @csrf

                                        <!-- Step 1: Personal Information -->
                                        <div id="step-personal" class="registration-step">
                                            <h5 class="text-primary mb-4">Personal Information</h5>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <x-input-label for="first_name" :value="__('First Name')" />
                                                        <x-text-input id="first_name" type="text" name="first_name"
                                                            required placeholder="Enter first name" />
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <x-input-label for="last_name" :value="__('Last Name')" />
                                                        <x-text-input id="last_name" type="text" name="last_name"
                                                            required placeholder="Enter last name" />
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <x-input-label for="email" :value="__('Email')" />
                                                        <x-text-input id="email" type="email" name="email" required
                                                            placeholder="Enter email address" />
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <x-input-label for="phone_number" :value="__('Phone Number')" />
                                                        <x-text-input id="phone_number" type="text" name="phone_number"
                                                            required placeholder="Enter phone number" />
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <x-input-label for="password" :value="__('Password')" />
                                                <x-text-input id="password" type="password" name="password" required
                                                    placeholder="Enter password" />
                                                <div class="invalid-feedback"></div>
                                            </div>


                                            <div class="d-flex justify-content-end">
                                                <button type="button" id="nextToAcademic"
                                                    class="btn btn-primary">Next</button>
                                            </div>
                                        </div>

                                        <!-- Step 2: Academic Information -->
                                        {{-- <div id="step-academic" class="registration-step"> --}}
                                        <div id="step-academic" class="registration-step" style="display:none;">
                                            <h5 class="text-primary mb-4">Academic Information</h5>

                                            <div class="mb-3">
                                                <x-input-label for="program" :value="__('Program')" />
                                                <select id="program" name="program_id" class="form-select" required>
                                                    <option value="">Select Program</option>
                                                    @foreach ($programs as $program)
                                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <div class="mb-3">
                                                <x-input-label for="school" :value="__('School')" />
                                                <select id="school" name="school_id" class="form-select" required
                                                    disabled>
                                                    <option value="">Select School</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <div class="mb-3">
                                                <x-input-label for="department" :value="__('Department')" />
                                                <select id="department" name="department_id" class="form-select" required
                                                    disabled>
                                                    <option value="">Select Department</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="mb-3">
                                                <x-input-label for="level" :value="__('Level')" />
                                                <select id="level" name="level_id" class="form-select" required
                                                    disabled>
                                                    <option value="">Select Level</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <button type="button" id="backToPersonal"
                                                    class="btn btn-secondary">Back</button>
                                                <button type="submit" class="btn btn-primary">Register</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="mt-5 text-center">
                                    <p class="mb-0">Already have an account ? <a href="{{ route('login') }}"
                                            class="fw-semibold text-primary text-decoration-underline"> Signin</a> </p>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->

        </div>
        <!-- end row -->
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            // Step Navigation
            $('#nextToAcademic').on('click', function() {
                if (validatePersonalStep()) {
                    $('#step-personal').hide();
                    $('#step-academic').show();
                }
            });

            $('#backToPersonal').on('click', function() {
                $('#step-academic').hide();
                $('#step-personal').show();
            });

            // Validation for Personal Step
            function validatePersonalStep() {
                let isValid = true;
                const personalStep = $('#step-personal');

                personalStep.find('[required]').each(function() {
                    const input = $(this);
                    const errorFeedback = input.next('.invalid-feedback');

                    if (!input.val().trim()) {
                        input.addClass('is-invalid');
                        errorFeedback.text('This field is required');
                        isValid = false;
                    } else {
                        input.removeClass('is-invalid');
                        errorFeedback.text('');
                    }
                });

                return isValid;
            }

            // Cascading Dropdowns (similar to previous implementation)
            $('#program').on('change', function() {
                const programId = $(this).val();
                const schoolSelect = $('#school');

                schoolSelect.html('<option value="">Select Department</option>').prop('disabled', true);
                $('#level, #department').html('<option value="">Select</option>').prop('disabled', true);

                if (programId) {
                    $.ajax({
                        url: `/api/schools/${programId}`,
                        method: 'GET',
                        success: function(schools) {
                            schools.forEach(function(school) {
                                schoolSelect.append(
                                    `<option value="${school.id}">${school.name}</option>`
                                );
                            });
                            schoolSelect.prop('disabled', false);
                        },
                        error: handleAjaxError
                    });
                }
            });

            $('#school').on('change', function() {
                const schoolId = $(this).val();
                const departmentSelect = $('#department');

                departmentSelect.html('<option value="">Select Department</option>').prop('disabled', true);
                $('#level').html('<option value="">Select</option>').prop('disabled', true);

                if (schoolId) {
                    $.ajax({
                        url: `/api/departments/${schoolId}`,
                        method: 'GET',
                        success: function(schools) {
                            schools.forEach(function(school) {
                                departmentSelect.append(
                                    `<option value="${school.id}">${school.name}</option>`
                                );
                            });
                            departmentSelect.prop('disabled', false);
                        },
                        error: handleAjaxError
                    });
                }
            });

            $('#department').on('change', function() {
                const departmentId = $(this).val();
                const levelSelect = $('#level');

                levelSelect.html('<option value="">Select Level</option>').prop('disabled', true);

                if (departmentId) {
                    $.ajax({
                        url: `/api/levels/${departmentId}`,
                        method: 'GET',
                        success: function(levels) {
                            levels.forEach(function(level) {

                                levelSelect.append(
                                    `<option value="${level.degree_level_id}">${level.degree_level.name}</option>`
                                );
                            });
                            levelSelect.prop('disabled', false);
                        },
                        error: handleAjaxError
                    });
                }
            });

            // Similar event listeners for department, program, and level

            // Form Submission
            $('#registrationForm').on('submit', function(e) {
                e.preventDefault();

                // Validate both steps before submission
                if (validatePersonalStep() && validateAcademicStep()) {
                    $.ajax({
                        url: '{{ route('student.register') }}',
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            window.location.href = response.redirect;
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                displayValidationErrors(xhr.responseJSON.errors);
                            }
                        }
                    });
                }
            });

            // Validate Academic Step
            function validateAcademicStep() {
                let isValid = true;
                const academicStep = $('#step-academic');

                academicStep.find('select[required]').each(function() {
                    const select = $(this);
                    const errorFeedback = select.next('.invalid-feedback');

                    if (!select.val()) {
                        select.addClass('is-invalid');
                        errorFeedback.text('Please select an option');
                        isValid = false;
                    } else {
                        select.removeClass('is-invalid');
                        errorFeedback.text('');
                    }
                });

                return isValid;
            }

            // Error handling function
            function handleAjaxError(xhr) {
                console.error('AJAX Error:', xhr);
                alert('An error occurred. Please try again.');
            }

            // Display validation errors
            function displayValidationErrors(errors) {
                $.each(errors, function(field, messages) {
                    const input = $(`[name="${field}"]`);
                    const errorFeedback = input.next('.invalid-feedback');

                    input.addClass('is-invalid');
                    errorFeedback.text(messages[0]);
                });
            }
        });
    </script>
@endsection
