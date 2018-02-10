<?php

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

Route::get('/index', function () {
    return view('welcome');
});

Route::group(['prefix' => 'index'], function () {
    $namespace = '\App\Core\GA\Controllers\\';
    Route::get('init', "{$namespace}IndexController@init");
    Route::get('addPoint', "{$namespace}IndexController@addPoint");
    Route::get('addRandomPoints', "{$namespace}IndexController@addRandomPoints");

    Route::get('start', "{$namespace}IndexController@start");
    Route::get('stop', "{$namespace}IndexController@stop");
    Route::get('test', "{$namespace}IndexController@test");
    Route::get('busy', "{$namespace}IndexController@busy");
});
