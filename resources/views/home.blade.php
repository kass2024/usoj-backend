@extends('layouts.student.guest')
@section('body')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card overflow-hidden">
                    <div class="row g-0">
                        <div class="col-lg-6">
                            <div class="p-lg-5 p-4 auth-one-bg h-100">
                                <div class="bg-overlay"></div>
                                <div class="position-relative h-100 d-flex flex-column">
                                    <div class="mb-4">

                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end col -->
eheh
                        <div class="col-lg-6">
                            <div class="p-lg-5 p-4">
                                <div>
                                    <h5 class="text-primary">Welcome Back !</h5>
                                    <p class="text-muted">Sign in to continue to Velzon.</p>
                                </div>
                                <x-auth-session-status class="mb-4" :status="session('status')" />


                                <div class="mt-4">
                                    <form method="POST" action="{{ route('student.login') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <x-input-label for="email" :value="__('Email')" />
                                            <x-text-input id="email" type="email" name="email" :value="old('email')"
                                                required autofocus autocomplete="username"
                                                placeholder="Enter email address" />
                                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                                        </div>

                                        <div class="mb-3">

                                            <x-input-label for="password-input" :value="__('Password')" />
                                            <div class="position-relative auth-pass-inputgroup mb-3">

                                                <x-text-input id="password" placeholder="Enter password"
                                                    class="pe-5 password-input" type="password" name="password" required
                                                    autocomplete="current-password" />

                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon"
                                                    type="button" id="password-addon"><i
                                                        class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value=""
                                                id="auth-remember-check">
                                            <label class="form-check-label" for="auth-remember-check">Remember
                                                me</label>
                                        </div>

                                        <div class="mt-4">
                                            <x-primary-button>
                                                {{ __('Log in') }}
                                            </x-primary-button>
                                        </div>
                                    </form>
                                </div>

                                <div class="mt-5 text-center">
                                    <p class="mb-0">Don't have an account ? <a href="auth-signup-cover.html"
                                            class="fw-semibold text-primary text-decoration-underline"> Signup</a> </p>
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
