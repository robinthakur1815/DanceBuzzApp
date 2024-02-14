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

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/db-seed', function () {
//     Artisan::call('db:seed');
//     return "database seeded";
// });

// Route::get('/config-cache', function () {
//     $exitCode = Artisan::call('config:cache');
//     return '<h1>Clear Config cleared</h1>';
// });

// Route::get("/blog/create", 'BlogController@create');
// Route::post("/img", 'UploadController@store');
// Route::get("/img/show/{upload}", 'UploadController@show');

Route::view('/cashfree/status/cancel', 'payment.cashfree.cancel')->name('cancel_cashfree');
Route::view('/cashfree/status/success', 'payment.cashfree.success')->name('success_cashfree');
Route::get('/cashfree/paymentflow/{id}/{payid}', 'Mobile\ApiCashFreePaymentController@paymentflow')->name('paymentflow');
Route::get('/cashfree/data/{id}/{payid}', 'Mobile\ApiCashFreePaymentController@apiPaymentData')->name('paymentData');
Route::post('/cashfree/status/{id}/{payid}', 'Mobile\ApiCashFreePaymentController@paymentStatus')->name('mobilePaymentStatus');
Route::post('/payment/notify', 'Mobile\ApiCashFreePaymentController@mobilePaymentNotify')->name('mobilePaymentNotify');

//dep
Route::get('/collection/editor/{id?}', 'Mobile\VendorCollectionController@editor');

Route::post('/oauth/token', 'Auth\LoginController@oAuthLogin');

Route::get('/mobiledownload/document/{id}', 'Api\ApiStoryController@mobiledownload')->name('mobiledownload');
Route::get('/download/document/{id}', 'Api\ApiStoryController@downloadFile')->name('download_file');
Route::get('/download/certificate/{id}', 'Api\ApiStoryController@certificatePDF');
Route::get('/getallcertificate', 'Api\ApiStoryController@getAllcertificatePDf');


Route::group(['prefix' => 'reports'], function () {
    Route::get('registration/{school}', 'Reports\RegistrationReportController@downloadReport');
    Route::get('registration/list', 'Reports\RegistrationReportController@downloadReport');
});

