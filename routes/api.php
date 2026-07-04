<?php

use App\Http\Controllers\ProgramsApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(ProgramsApiController::class)->group(function () {
    Route::get('/schools/{programId}', 'getSchools');
    Route::get('/departments/{schoolId}', 'getDepartments');
    Route::get('/levels/{departmentId}', 'getLevels');
});
