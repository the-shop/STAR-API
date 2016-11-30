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

// All API calls are prefixed with "api", and we're defining version of API "v1"
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This route group applies the "JWTAuth" middleware group to every route
| except for login and signup.
|
*/
Route::group(['prefix' => 'api/v1', 'middleware' => 'the-shop.requestLogger'], function()
{
    // API calls we allow without token authorization
    Route::post('register', 'ProfileController@store');
    Route::post('login', 'ProfileController@login');

    // Define a group of APIs that require auth (we use JWT Auth for token authorization)
    Route::group(['middleware' => 'jwt.auth'], function()
    {
        Route::put('profiles/changePassword', 'ProfileController@changePassword');
        Route::resource('profiles', 'ProfileController');
        Route::resource('validations', 'ValidationController');
        Route::put('projects/{id}/makeReservation', 'ReservationController@make');
        Route::put('projects/{id}/acceptReservation', 'ReservationController@accept');
        Route::put('projects/{id}/declineReservation', 'ReservationController@decline');
        Route::get('database/listCollections', 'DatabaseController@listCollections');
        Route::post('slack/message', 'SlackController@sendMessage');
        Route::get('slack/users', 'SlackController@getUsers');
        Route::get('slack/channels', 'SlackController@getChannels');
        Route::post('trello/board', 'TrelloController@createBoard');
        Route::post('trello/board/{id}/list', 'TrelloController@createList');
        Route::put('trello/list/{id}/ticket', 'TrelloController@createTicket');
        Route::put('trello/ticket/{id}/member/{id}/add', 'TrelloController@assignMember');
        Route::put('trello/ticket/{id}/member/{id}/remove', 'TrelloController@removeMember');
        Route::put('trello/ticket/{id}', 'TrelloController@setDueDate');



        /**
         * Generic resources routes
         *
         * All of routes use `the-shop.genericResource` middleware so that GenericModel gets injected
         * `$collection` from `resource` route paramter
         */
        Route::get('{resource}', 'GenericResourceController@index')->middleware('the-shop.genericResource');
        Route::get('{resource}/{id}', 'GenericResourceController@show')->middleware('the-shop.genericResource');
        Route::post('{resource}', 'GenericResourceController@store')->middleware('the-shop.genericResource');
        Route::put('{resource}/{id}', 'GenericResourceController@update')->middleware('the-shop.genericResource');
        Route::delete('{resource}/{id}', 'GenericResourceController@destroy')->middleware('the-shop.genericResource');
    });
});

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
    //
});
