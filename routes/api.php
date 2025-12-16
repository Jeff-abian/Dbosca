<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Don't forget to import your controllers!
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthorizationController; // The new controller for login/register

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The API routes file is automatically prefixed with '/api' and uses the
| 'api' middleware group.
|
*/
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    //return $request->user();
//});
// ===================================
// 1. PUBLIC AUTHENTICATION ROUTES
// These endpoints allow users to get an API token.
// ===================================
Route::post('/auth/register', [AuthorizationController::class, 'register']);
Route::post('/auth/login', [AuthorizationController::class, 'login']);


// ===================================
// 2. PROTECTED ROUTES
// All routes inside this group require a valid Sanctum token.
// ===================================
Route::middleware('auth:sanctum')->group(function () {

    // Basic test route (default) and Logout
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/auth/logout', [AuthorizationController::class, 'logout']);

    // Institution API Routes (Resource Declaration)

    Route::apiResource('users', UserController::class);
});
































































//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\Api\InstitutionController; // Don't forget to import!
//use App\Http\Controllers\Api\FavoriteController;
//use App\Http\Controllers\Api\TagController;
//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\Api\AuthController; // Add this line
// ... other imports ...


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The API routes file is automatically prefixed with '/api' and uses the 'api' middleware group.
|
*/

// Basic test route (default)
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    //return $request->user();
//});


// Institution API Routes (Resource Declaration)
// This single line creates the 5 necessary CRUD endpoints:
// GET (index), POST (store), GET/{id} (show), PUT/PATCH/{id} (update), DELETE/{id} (destroy)
//Route::apiResource('institutions', InstitutionController::class);
//Route::apiResource('favorites', FavoriteController::class);
//Route::apiResource('Tag', TagController::class);
// --- Public Routes ---
//Route::post('/auth/register', [AuthController::class, 'register']);
//Route::post('/auth/login', [AuthController::class, 'login']);
