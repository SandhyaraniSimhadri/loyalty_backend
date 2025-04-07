<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymobController;

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


Route::post('update_user_info', 'UsersController@update_user_info');

Route::post('ProfileInfo', 'UsersController@ProfileInfo');
Route::post('userScore', 'UsersController@userScore');
Route::post('gameUserLogin', 'UsersController@gameUserLogin');



Route::post('add_company', 'CompanyController@add_company');

Route::group(['middleware' => ['cors']], function () {
    Route::post('verify_user', 'UsersController@verify_user');
    Route::post('set_registration', 'UsersController@set_registration');
    Route::middleware('api_check')->post('update_user_password', 'UsersController@update_user_password');
    Route::middleware('api_check')->post('update_social_media_account', 'UsersController@update_social_media_account');




    Route::post('check_user', 'UsersController@check_user');
    Route::post('set_password', 'UsersController@set_password');

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
    Route::post('get_single_campaign', 'CampaignsController@get_single_campaign');
    Route::middleware('api_check')->post('get_report', 'CampaignsController@get_report');
    Route::post('update_campaign', 'CampaignsController@update_campaign');
    Route::post('delete_campaign', 'CampaignsController@delete_campaign');
    Route::middleware('api_check')->get('get_campaigns', 'CampaignsController@get_campaigns');
    Route::post('users_file_import','UsersModuleController@users_file_import');
    Route::post('get_users_report', 'UsersModuleController@get_users_report');

    Route::post('select_winner', 'CampaignsController@select_winner');
    Route::get('points_for_participant', 'PredictionsController@points_for_participant');


    Route::middleware('api_check')->post('get_prediction_details', 'PredictionsController@get_prediction_details');
    Route::middleware('api_check')->post('add_prediction_winner', 'PredictionsController@add_prediction_winner');
    Route::post('send_invitation','UsersModuleController@send_invitation');
});

Route::get('download_users_sample', 'UsersModuleController@download_users_sample');
// Route::post('/paymob/authenticate', 'PaymobController@authenticate');
// Route::post('/paymob/create-order', 'PaymobController@createOrder');
// Route::post('/paymob/payment-key', 'PaymobController@generatePaymentKey');
// Route::post('/paymob/webhook', 'PaymobController@handleWebhook');

// Route::post('/paymob/authenticate', [PaymobController::class, 'authenticate']);
// Route::post('/paymob/create-order', [PaymobController::class, 'createOrder']);
// Route::post('/paymob/payment-key', [PaymobController::class, 'generatePaymentKey']);
// Route::post('/paymob/webhook', [PaymobController::class, 'handleWebhook']);

Route::post('/paymob/authenticate', [PaymobController::class, 'authenticate']);
Route::post('/paymob/create-order', [PaymobController::class, 'createOrder']);
Route::post('/paymob/payment-key', [PaymobController::class, 'generatePaymentKey']);