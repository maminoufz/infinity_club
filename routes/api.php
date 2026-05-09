<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SpecialiteController;
use App\Http\Controllers\LinkController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {

    // api de auth (http//localhost:8000/api/auth/{nom de route})
    Route::put('/user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
    // api for auth

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');

    // Add a new department (admin role required)
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::put('departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('departments/{id}', [DepartmentController::class, 'destroy']);

    // api de sp
    Route::post('/specialites', [SpecialiteController::class, 'store']);
    Route::put('/specialites/{id}', [SpecialiteController::class, 'update']);
    Route::delete('/specialites/{id}', [SpecialiteController::class, 'destroy']);

    // api events
    Route::post('events', 'App\\Http\\Controllers\\EventController@addEvent');
    Route::put('events/{id}', 'App\\Http\\Controllers\\EventController@updateEvent');
    Route::delete('events/{id}', 'App\\Http\\Controllers\\EventController@deleteEvent');

});
// api link

Route::middleware('auth:api')->post('/links', [LinkController::class, 'addLink']);
Route::middleware('auth:api')->put('/links/{linkId}', [LinkController::class, 'updateLink']);
Route::middleware('auth:api')->delete('/links/{linkId}', [LinkController::class, 'deleteLink']);
Route::get('/links/{userId}', [LinkController::class, 'showLinks']);


// Get all departments whithout auth
Route::get('user', [AuthController::class, 'user'])->middleware('auth:api');

Route::get('departments', [DepartmentController::class, 'index']);
Route::get('/specialites', [SpecialiteController::class, 'index']);
Route::get('events', 'App\\Http\\Controllers\\EventController@getAllEvents');
Route::get('departments/{id}/specialites', [DepartmentController::class, 'getSpecialitesByDepartment']);
Route::get('specialites/{id}/users', [SpecialiteController::class, 'getUsersBySpecialite']);
Route::get('/links/{userId}', [LinkController::class, 'showLinks']);
