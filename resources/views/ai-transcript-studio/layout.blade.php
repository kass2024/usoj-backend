@extends('layouts.app')

@section('css')
<style>
    .ai-studio-nav .list-group-item {
        border-left: 3px solid transparent;
        padding: 0.65rem 1rem;
    }
    .ai-studio-nav .list-group-item.active {
        border-left-color: #007a33;
        background: #f0faf4;
        color: #007a33;
        font-weight: 600;
    }
    .ai-studio-nav .list-group-item i {
        width: 1.25rem;
    }
    .ai-studio-stat {
        font-size: 0.75rem;
        color: #6c757d;
    }
</style>
@endsection

@section('body')
<div class="row g-4">
    <div class="col-lg-3 col-xl-2">
        @include('ai-transcript-studio.partials.sidebar-nav')
    </div>
    <div class="col-lg-9 col-xl-10">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @yield('studio_content')
    </div>
</div>
@endsection
