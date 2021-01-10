<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//Auth::routes(['register' => false]);//デフォルトのアカウント登録機能のOFF
Route::get('{any?}', function () {
    return view('index');
})->where('any', '.*')->name('login');

Route::post('/deleteMembers', [MemberController::class, 'deleteMembers']);
/*
Route::get('/', function () {
    return view('welcome');
});
*/
//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
