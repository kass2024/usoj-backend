<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\LectureController;
use App\Http\Controllers\QuizzesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassStudentController;
use App\Http\Controllers\Settings\CourseController;
use App\Http\Controllers\Settings\SchoolController;
use App\Http\Controllers\Settings\ClassesController;
use App\Http\Controllers\Settings\ProgramController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\AcademicYearController;
use App\Http\Controllers\Settings\DegreeLevelsController;
use App\Http\Controllers\StudentPhotoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */

Route::get('/', function () {
    return to_route('login');
});

Route::get('/student-photos/{student}', [StudentPhotoController::class, 'show'])
    ->name('student-photos.show');

Route::middleware('auth')->group(function () {

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
    })->middleware(['verified']);

    Route::resource('users', UserController::class);
    Route::get('users-all', [UserController::class, 'users'])->name('users.all');
    Route::get('heads-of-department', [UserController::class, 'heads_of_departments'])->name('users.heads_of_departments');
    Route::get('heads-of-department-list', [UserController::class, 'heads_of_departments_list'])->name('users.heads_of_departments_all');
    Route::get('lectures', [UserController::class, 'lectures'])->name('users.lectures');
    Route::get('lectures-list', [UserController::class, 'lectures_list'])->name('users.lectures_all');
    Route::resource('students', StudentController::class);

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::resource('programs', ProgramController::class);
        Route::resource('degree-levels', DegreeLevelsController::class);
        Route::get('degree-levels/category/{program_id}', [DegreeLevelsController::class, 'category'])->name('degree-levels.category');
        Route::resource('schools', SchoolController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('classes', ClassesController::class);
        Route::resource('courses', CourseController::class);
        Route::resource('academic-years', AcademicYearController::class);

    });

    Route::controller(ClassStudentController::class)->name('hod.')->group(function () {
        Route::get('/{slug}/students/{year}', 'index')->name('students.index');
        Route::post('/hod/add-student/{year}', 'store')->name('students.store');
        Route::delete('/hod/delete-student/{id}', 'destroy')->name('students.destroy');
    });

    Route::controller(ModuleController::class)->name('hod.')->group(function () {
        Route::get('/{slug}/modules/{year}', 'index')->name('modules.index');
        Route::post('/hod/add-modules/{year}', 'store')->name('modules.store');
        Route::put('/hod/update-modules/{id}', 'update')->name('modules.update');
        Route::delete('/hod/delete-module/{id}', 'destroy')->name('modules.destroy');
    });



    Route::name('lecture.')->prefix('lecture')->group(function () {
        Route::controller(LectureController::class)->group(function () {
            Route::get('/modules-and-students/{year}', 'index')->name('index');
            Route::get('/module-details/{id}', 'module')->name('module');
            Route::get('/exams/{id}', 'exams')->name('exams');
            Route::get('/assignments/{id}', 'assignments')->name('assignments');
            Route::get('/quizzes/{id}', 'quizzes')->name('quizzes');
            Route::get('/student/{id}', 'student')->name('student');
            // manage lessons
            Route::post('/lesson/{id}', 'store_lesson')->name('store_lesson');
            Route::put('/lesson/{id}', 'update_lesson')->name('update_lesson');
            Route::delete('/lesson/{id}', 'delete_lesson')->name('delete_lesson');
        });

        Route::controller(QuizzesController::class)->name('quiz.')->group(function () {
            Route::post('/quiz/{id}', 'store')->name('store');
            Route::put('/quiz/{id}', 'update')->name('update');
            Route::delete('/quiz/{id}', 'delete')->name('delete');
            Route::get('/quizzes/questions/{id}', 'questions')->name('questions');
            Route::get('/quizzes/questions/{id}/create', 'create')->name('create');
            Route::get('/quizzes/questions/{id}/edit', 'edit')->name('edit');
            Route::post('/quizzes/questions/{id}/store', 'store_question')->name('store_question');
            Route::put('/quizzes/questions/{id}/update', 'update_question')->name('update_question');
            Route::delete('/quizzes/questions/{id}/delete', 'delete_question')->name('delete_question');

        });

        Route::controller(AssignmentController::class)->name('assignment.')->group(function () {
            Route::post('/assignment/{id}', 'store')->name('store');
            Route::put('/assignment/{id}', 'update')->name('update');
            Route::delete('/assignment/{id}', 'delete')->name('delete');
            Route::get('/assignment/questions/{id}', 'questions')->name('questions');
            Route::get('/assignment/questions/{id}/create', 'create')->name('create');
            Route::get('/assignment/questions/{id}/edit', 'edit')->name('edit');
            Route::post('/assignment/questions/{id}/store', 'store_question')->name('store_question');
            Route::put('/assignment/questions/{id}/update', 'update_question')->name('update_question');
            Route::delete('/assignment/questions/{id}/delete', 'delete_question')->name('delete_question');

        });

        Route::controller(ExamController::class)->name('exam.')->group(function () {
            Route::post('/exam/{id}', 'store')->name('store');
            Route::put('/exam/{id}', 'update')->name('update');
            Route::delete('/exam/{id}', 'delete')->name('delete');
            Route::get('/exam/questions/{id}', 'questions')->name('questions');
            Route::get('/exam/questions/{id}/create', 'create')->name('create');
            Route::get('/exam/questions/{id}/edit', 'edit')->name('edit');
            Route::post('/exam/questions/{id}/store', 'store_question')->name('store_question');
            Route::put('/exam/questions/{id}/update', 'update_question')->name('update_question');
            Route::delete('/exam/questions/{id}/delete', 'delete_question')->name('delete_question');

        });
    });


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/student.php';
require __DIR__ . '/certificatesRoutes.php';
