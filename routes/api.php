<?php

use Illuminate\Http\Request;

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

Route::namespace('Api')->group(function () {
    // 注册
    Route::post('register', 'UsersController@register')->name('users.register');
    // 登录
    Route::post('login', 'UsersController@login')->name('login');

});


Route::middleware('auth:api')->namespace('Api')->group(function () {
    Route::get('info', 'UsersController@info')->name('users.info');
    // 刷新token
    Route::put('refresh', 'UsersController@update')->name('users.update');
    // 删除token
    Route::post('logout', 'UsersController@destroy')->name('users.destroy');
});
