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

Route::get('ajg', function () { return view('welcome'); });
//Route::get('w', function () { return view('email/emailForgotPassword'); });
Route::get('/', function () { return 'la godeg'; });
//Route::get('sysinfo/',['as'=>'sysinfo.index',   'uses'=>'SysinfoController@index']);
