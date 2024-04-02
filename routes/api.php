<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});





// Route::group(['middleware' => ['cors']], function () {
Route::post('verify_user', 'UsersController@verify_user');

Route::post('add_company', 'CompanyController@add_company');
Route::post('get_single_company', 'CompanyController@get_single_company');
Route::post('update_company', 'CompanyController@update_company');
Route::post('delete_company', 'CompanyController@delete_company');
Route::get('get_companies', 'CompanyController@get_companies');


Route::post('add_event', 'EventsController@add_event');
Route::middleware('api_check')->post('get_single_event', 'EventsController@get_single_event');
Route::post('update_event', 'EventsController@update_event');
Route::post('delete_event', 'EventsController@delete_event');
Route::middleware('api_check')->get('get_events', 'EventsController@get_events');


Route::post('add_user', 'UsersModuleController@add_user');
Route::middleware('api_check')->post('get_single_user', 'UsersModuleController@get_single_user');
Route::post('update_user', 'UsersModuleController@update_user');
Route::post('delete_user', 'UsersModuleController@delete_user');
Route::middleware('api_check')->get('get_users', 'UsersModuleController@get_users');


Route::post('add_campaign', 'CampaignsController@add_campaign');
Route::middleware('api_check')->post('get_single_campaign', 'CampaignsController@get_single_campaign');
Route::post('update_campaign', 'CampaignsController@update_campaign');
Route::post('delete_campaign', 'CampaignsController@delete_campaign');
Route::middleware('api_check')->get('get_campaigns', 'CampaignsController@get_campaigns');

// });



















