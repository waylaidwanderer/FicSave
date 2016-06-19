<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    Route::get('/', function(\Illuminate\Http\Request $request) {
        $request->session()->set('currentId', uniqid());
        return view('index');
    });
    Route::group(['prefix' => 'api'], function() {
        Route::group(['prefix' => 'downloader'], function() {
            Route::post('begin', 'API\DownloaderController@postBegin')->name('download-begin');
            Route::post('process', 'API\DownloaderController@postProcess')->name('download-process');
        });
    });

    Route::get('download/{bookId}', 'DownloadController@getIndex')->name('download');
});

Route::group(['middleware' => ['api']], function() {
    Route::group(['prefix' => 'api'], function() {
        Route::group(['prefix' => 'donation'], function() {
            Route::post('paypal/new', 'API\DonationController@postPaypal');
        });
    });
});
