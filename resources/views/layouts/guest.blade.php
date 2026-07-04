<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name') }} — Sign In</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/usj-crest.png') }}?v=7">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link href="{{ asset('assets/css/auth.css') }}?v=5" rel="stylesheet">
</head>
<body>
    <div class="login-page">

        {{-- Animated background --}}
        <div class="login-bg" aria-hidden="true">
            <div class="login-bg__mesh"></div>
            <div class="login-bg__orb login-bg__orb--1"></div>
            <div class="login-bg__orb login-bg__orb--2"></div>
            <div class="login-bg__orb login-bg__orb--3"></div>
            <div class="login-bg__grid"></div>
            <div class="login-bg__shine"></div>
        </div>

        <div class="login-center">

            <x-usj-brand layout="stacked" />

            <p class="login-portal-tag">E-Learning Portal</p>

            <div class="login-card">
                {{ $slot }}
            </div>

            <footer class="login-foot">
                &copy; {{ date('Y') }} {{ config('app.name') }}
                <div class="login-foot__contact">
                    <span>
                        <i class="ri-mail-line"></i>
                        <span class="login-foot__label">Email:</span>
                        <a href="mailto:uosj@uosj.ac.ug">uosj@uosj.ac.ug</a>
                    </span>
                    <span>
                        <i class="ri-global-line"></i>
                        <span class="login-foot__label">Website:</span>
                        <a href="https://www.uosj.ac.ug" target="_blank" rel="noopener">www.uosj.ac.ug</a>
                    </span>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
