<?php

use App\Http\Controllers\CertificatesController;
use App\Http\Controllers\CoursesSchoolViewController;
use App\Http\Controllers\StudentCreateController;
use App\Http\Controllers\StudentController; // CRUD for modal actions (store/update/destroy)

// ---------------- Certificates (as you had) ----------------
Route::controller(CertificatesController::class)
    ->middleware('auth')
    ->prefix('certificates')
    ->name('certificates.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'verifyRegNumber')->name('verify');
        Route::post('/{id}/photo', 'uploadPhoto')->name('photo');
        Route::delete('/{id}/photo', 'deletePhoto')->name('photo.delete');
        Route::post('/{id}/email', 'emailDocuments')->name('email');
        Route::get('/{id}/transcript', 'generateTranscript')->name('transcript');
        Route::get('/{id}/degree', 'generateDegree')->name('degree');
    });

// ---------------- Courses (as you had) ----------------
Route::controller(CoursesSchoolViewController::class)
    ->middleware('auth')
    ->prefix('courses')
    ->name('courses.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/programs/{program}/schools', 'schoolsByProgram')->name('schools.byProgram');
        Route::get('/schools/{school}/departments', 'departmentsBySchool')->name('departments.bySchool');
        Route::get('/departments/{department}/courses', 'coursesByDepartment')->name('courses.byDepartment');
    });

// ---------------- Students (cascade JSON + page) ----------------
// Put this BEFORE the resource so /students/... JSON endpoints aren't shadowed
Route::controller(StudentCreateController::class)
    ->middleware('auth')
    ->prefix('students')
    ->name('students.')
    ->group(function () {
        Route::get('/', 'index')->name('index'); // page
        Route::get('/programs/{program}/schools', 'schoolsByProgram')->name('schools.byProgram');
        Route::get('/schools/{school}/departments', 'departmentsBySchool')->name('departments.bySchool');
        Route::get('/departments/{department}/levels', 'levelsByDepartment')->name('levels.byDepartment');
        Route::get('/departments/{department}/students', 'studentsByDepartment')->name('byDepartment');
    });

// ---------------- Students CRUD used by the modal ----------------
// If you already have a different controller for these, keep it — just ensure route names match in Blade.
Route::resource('students', StudentController::class)
    ->only(['store','update','destroy'])
    ->middleware('auth');
