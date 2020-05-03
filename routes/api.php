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
    // 删除token
    Route::post('logout', 'UsersController@destroy')->name('users.destroy');
});


Route::middleware('auth:api')->namespace('Api')->group(function () {
    Route::get('info', 'UsersController@info')->name('users.info');
    Route::post('create', 'LessonsController@create')->name('lessons.create');
    Route::get('lesson-list', 'LessonsController@lessonList')->name('lessons.lessonList');
    Route::post('join', 'LessonsController@join')->name('lessons.join');
    Route::get('my-lesson', 'LessonsController@myLesson')->name('lessons.myLesson');
    Route::get('student-list', 'LessonsController@studentList')->name('lessons.studentList');
    Route::post('start-sign-in','LessonsController@startSignIn')->name('lessons.startSignIn');
    Route::post('end-sign-in','LessonsController@endSignIn')->name('lessons.endSignIn');
    Route::get('sign-in-history','LessonsController@signInHistory')->name('lessons.signInHistory');
    Route::get('sign-in-detail','LessonsController@signInDetail')->name('lessons.signInDetail');
    Route::get('sign-in-status', 'LessonsController@signInStatus')->name('lessons.signInStatus');
    Route::post('upload', 'LessonsController@upload')->name('lessons.upload');
    Route::get('file-list', 'LessonsController@fileList')->name('lessons.fileList');
    Route::post('delete', 'LessonsController@delete')->name('lessons.delete');
    Route::post('delete-student', 'LessonsController@deleteStudent')->name('lessons.deleteStudent');
});
