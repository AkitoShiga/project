<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\HolidayController;

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
Route::middleware( 'auth:sanctum' )->get( '/user', function( Request $request ){ return $request->user(); } );
Route::get(  '/getMembers'          , [ MemberController::class   , 'getMembers' ]);
Route::post( '/deleteMembers'       , [ MemberController::class   , 'deleteMembers' ]);
Route::post( '/addMembers'          , [ MemberController::class   , 'addMembers' ]);
Route::post( '/login'               , [ LoginController::class    , 'login' ]);
Route::post( '/logout'              , [ LoginController::class    , 'logout' ]);
Route::post( '/getSchedule'         , [ ScheduleController::class , 'getSchedule' ]);
Route::post( '/getTotalWorkingHours', [ ScheduleController::class , 'getTotalWorkingHours' ]);
Route::post( '/checkHoliday'        , [ HolidayController::class  , 'checkHoliday' ] );
