<!doctype html>
<html lang="en" data-layout="vertical" data-layout-style="detached" data-layout-position="fixed" data-topbar="light"
    data-sidebar="dark" data-sidebar-size="lg" data-layout-width="fluid">

<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'University E-learning') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="University of Saint Joseph Mbarara E-learning" name="description" />
    <meta content="BmgCodes" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('images/usj-crest.png') }}">
    @yield('css')
    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/toast/css/jquery.toast.css') }}" rel="stylesheet" type="text/css" />

    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('layouts.student.navbar')


        <!-- ========== App Menu ========== -->
        @include('layouts.student.sidebar')

        <!-- Left Sidebar End -->
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">
                    @yield('body')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">

                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                <x-footer-label />
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->



    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>

    <script src="{{ asset('assets/toast/jquery.js') }}"></script>
    <script src="{{ asset('assets/toast/js/jquery.toast.js') }}"></script>
    {{-- <script src="/assets/js/plugins.js"></script> --}}
    @yield('js')
    <!-- App js -->
    <script>
        $(document).ready(function() {
            $("form").submit(function(event) {
                $(this).find("button[type=submit]").addClass('btn btn-outline-primary btn-load').html(`<span class="d-flex align-items-center">
                                        <span class="spinner-border flex-shrink-0" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                        <span class="flex-grow-1 ms-2">
                                            Loading...
                                        </span>
                                    </span>`).prop("disabled", true);
            });
        });
    </script>

    @if (session()->has('success'))
        <script>
            $(document).ready(function() {
                $.toast({
                    heading: 'Success',
                    text: '{{ session()->get('success') }}',
                    showHideTransition: 'fade',
                    icon: 'success',
                    position: 'top-right'
                });
            });
        </script>
    @endif
    @if (session()->has('message'))
        <script>
            $(document).ready(function() {
                $.toast({
                    heading: 'Success',
                    text: '{{ session()->get('message') }}',
                    showHideTransition: 'fade',
                    icon: 'success',
                    position: 'top-right'
                });
            });
        </script>
    @endif
    @if (session()->has('warning'))
        <script>
            $(document).ready(function() {
                $.toast({
                    heading: 'Message',
                    text: '{{ session()->get('warning') }}',
                    showHideTransition: 'fade',
                    icon: 'warning',
                    position: 'top-right'
                });
            });
        </script>
    @endif
    @if (session()->has('error'))
        <script>
            $(document).ready(function() {
                $.toast({
                    heading: 'Error',
                    text: '{{ session()->get('error') }}',
                    showHideTransition: 'fade',
                    icon: 'error',
                    position: 'top-right'
                });
            });
        </script>
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
        @endforeach
        @php
            $data = 'Error Accurs';
        @endphp
        <script>
            $(document).ready(function() {
                $.toast({
                    heading: 'Error',
                    text: '{{ $error }}',
                    showHideTransition: 'fade',
                    icon: 'error',
                    position: 'top-right',
                    hideAfter: 5000,
                });
            });
        </script>
    @endif
    <script>
        $(document).ready(function() {
            $("form").submit(function(event) {
                $(this).find("button[type=submit]").prop("disabled", true);
            });
        });
    </script>


    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>
