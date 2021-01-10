<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
use App\Http\Controllers\Auth\Api\LoginController;
use App\Http\Controllers\Auth\Api\RegisterController;
*/
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MemberController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
/*
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('user', function(Request $request) {
        return response()->json(['user' => $request->user()]);
    });
    Route::post('logout', [LoginController::class,'logout'])->name('api.logout');
});

Route::post('register', [RegisterController::class, 'register'])->name('api.register');
Route::post('login', [LoginController::class, 'login'])->name('api.login');
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/getMembers', [MemberController::class, 'getMembers']);
Route::post('/deleteMembers', [MemberController::class, 'deleteMembers']);
Route::get('/deleteMembers', [MemberController::class, 'deleteMembers']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
