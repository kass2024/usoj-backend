<?php

use App\Http\Controllers\AiAssessmentManagementController;
use App\Http\Controllers\AiTranscriptStudioController;
use App\Http\Controllers\CertificatesController;
use App\Http\Controllers\CoursesSchoolViewController;
use App\Http\Controllers\Settings\CourseController;
use App\Http\Controllers\DocumentUploadLinkController;
use App\Http\Controllers\DocumentUploadPortalController;
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
        Route::get('/{id}/transcript-readiness', 'transcriptReadiness')->name('transcript.readiness');
        Route::post('/{id}/transcript-profile', 'updateTranscriptProfile')->name('transcript.profile');
        Route::get('/{id}/transcript', 'generateTranscript')->name('transcript');
        Route::get('/{id}/degree', 'generateDegree')->name('degree');
        Route::get('/{id}/external-transcript', 'viewExternalTranscript')->name('external.transcript');
        Route::get('/{id}/external-degree', 'viewExternalDegree')->name('external.degree');
    });

// ---------------- AI Transcript Studio (Gemini + bot auto-mark) ----------------
Route::controller(AiTranscriptStudioController::class)
    ->middleware('auth')
    ->prefix('ai-transcript-studio')
    ->name('ai-transcript-studio.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/lookup', 'lookup')->name('lookup');
        Route::get('/run', 'runRedirect')->name('run.redirect');
        Route::post('/run', 'run')->name('run');
        Route::get('/runs/{run}/progress', 'progress')->name('run.progress');
        Route::post('/runs/{run}/cancel', 'cancel')->name('run.cancel');
        Route::delete('/runs/{run}', 'destroy')->name('run.destroy');
        Route::get('/runs/{run}', 'showRun')->name('run.show');
        Route::get('/students/{student}/transcript', 'generateTranscript')->name('transcript');
    });

Route::controller(AiAssessmentManagementController::class)
    ->middleware('auth')
    ->prefix('ai-transcript-studio')
    ->name('ai-transcript-studio.')
    ->group(function () {
        Route::get('/assessments', 'assessments')->name('assessments.index');
        Route::get('/assessments/{type}/{id}', 'showAssessment')->name('assessments.show');
        Route::get('/marking', 'marking')->name('marking.index');
        Route::get('/marking/students/{student}', 'showStudentMarking')->name('marking.show');
        Route::patch('/marking/submissions/{submission}', 'updateSubmissionMark')->name('marking.update');
        Route::get('/runs-history', 'runs')->name('runs.index');
    });

// ---------------- DMI: manage private upload links (staff) ----------------
Route::controller(DocumentUploadLinkController::class)
    ->middleware('auth')
    ->prefix('document-links')
    ->name('document-links.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{link}/toggle', 'toggle')->name('toggle');
        Route::delete('/{link}', 'destroy')->name('destroy');
    });

// ---------------- DMI: private upload portal (username/password via link) ----------------
Route::prefix('upload-docs/{slug}')
    ->name('document-portal.')
    ->group(function () {
        Route::get('/login', [DocumentUploadPortalController::class, 'showLogin'])->name('login');
        Route::post('/login', [DocumentUploadPortalController::class, 'login'])->name('login.submit');
        Route::post('/logout', [DocumentUploadPortalController::class, 'logout'])->name('logout');

        Route::middleware('document.portal')->group(function () {
            Route::get('/', [DocumentUploadPortalController::class, 'dashboard'])->name('dashboard');
            Route::post('/lookup', [DocumentUploadPortalController::class, 'lookup'])->name('lookup');
            Route::post('/upload', [DocumentUploadPortalController::class, 'upload'])->name('upload');
        });
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
        Route::get('/departments/{department}/levels', 'levelsByDepartment')->name('levels.byDepartment');
        Route::get('/departments/{department}/courses', 'coursesByDepartment')->name('courses.byDepartment');
        Route::post('/bulk-text-parse', [CourseController::class, 'parseBulkText'])->name('bulkTextParse');
        Route::post('/bulk-text-import', [CourseController::class, 'bulkTextImport'])->name('bulkTextImport');
        Route::post('/bulk-delete/challenge', [CourseController::class, 'bulkDeleteChallenge'])->name('bulkDeleteChallenge');
        Route::delete('/bulk-delete', [CourseController::class, 'bulkDeleteAll'])->name('bulkDeleteAll');
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
