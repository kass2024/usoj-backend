<?php

use App\Http\Controllers\Student\AssignmentsController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ExamsController;
use App\Http\Controllers\Student\MarksViewController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\CoursesViewController;
use App\Http\Controllers\Student\QuizzesController;
use App\Http\Controllers\StudentAuth\AuthenticatedSessionController;
use App\Http\Controllers\StudentAuth\ConfirmablePasswordController;
use App\Http\Controllers\StudentAuth\EmailVerificationNotificationController;
use App\Http\Controllers\StudentAuth\EmailVerificationPromptController;
use App\Http\Controllers\StudentAuth\NewPasswordController;
use App\Http\Controllers\StudentAuth\PasswordController;
use App\Http\Controllers\StudentAuth\PasswordResetLinkController;
use App\Http\Controllers\StudentAuth\RegisteredUserController;
use App\Http\Controllers\StudentAuth\VerifyEmailController;
use App\Http\Controllers\Auth\AuthenticatedSessionController as UnifiedAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->name('student.')->prefix('student')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', fn () => redirect()->route('login'))->name('login');

    Route::post('login', [UnifiedAuthController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('student')->name('student.')->prefix('student')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('logout', [UnifiedAuthController::class, 'destroy'])
        ->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('/module/{id}', 'module')->name('module');
    });

    Route::controller(QuizzesController::class)->prefix('quizzes')->name('quizzes.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/submission/{quiz}', 'submission')->name('submission');
        Route::post('/submission/{quiz}', 'save_submission')->name('save_submission');
        Route::get('/view_submission/{submission}', 'view_submission')->name('view_submission');
    });

    Route::controller(AssignmentsController::class)->prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/submission/{assignment}', 'submission')->name('submission');
        Route::post('/submission/{assignment}', 'save_submission')->name('save_submission');
        Route::get('/view_submission/{submission}', 'view_submission')->name('view_submission');
    });
       Route::controller(MarksViewController::class)->prefix('marks')->name('marks.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
      
    });
     Route::controller(CoursesViewController::class)->prefix('courses')->name('courses.')->group(function () {
        Route::get('/', 'index')->name('index');
    
      
    });


    Route::controller(ExamsController::class)->prefix('exams')->name('exams.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/submission/{exam}', 'submission')->name('submission');
        Route::post('/submission/{exam}', 'save_submission')->name('save_submission');
        Route::get('/view_submission/{submission}', 'view_submission')->name('view_submission');
    });
});

