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
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['cors'])->group(function () {
  //users
  Route::post('/v1/signUp', 'API\UserController@signUp');
  Route::post('/v1/login', 'API\UserController@login');
  Route::post('/v1/logout', 'API\UserController@logout');
  Route::get('/v1/activate/{id}', 'API\UserController@activation');
  Route::post('/v1/updatePassword', 'API\UserController@updatePassword');
  Route::post('/v1/updateProfile', 'API\UserController@updateProfile');
  Route::get('/v1/forgotPassword/{id}/{token}', 'API\UserController@forgotPassword');
  Route::get('/v1/flagDonation/{id}/{token}', 'API\UserController@flagDonation');
  Route::get('/v1/flagTopup/{id}/{token}', 'API\UserController@flagTopup');
  Route::post('/v1/signUpGuest', 'API\UserController@signUpGuest');

  //product
  Route::get('/v1/productView/{token}', 'API\ProductController@view');
  Route::get('/v1/productDetail/{id}/{token}', 'API\ProductController@detail');
  Route::post('/v1/productSearch', 'API\ProductController@search');
  Route::get('/v1/productSimilar/{id}/{token}', 'API\ProductController@similar');
  Route::get('/v1/productHighlight/{token}', 'API\ProductController@highlight');
  Route::get('/v1/productViewByShop/{id}/{token}', 'API\ProductController@viewByShop');

  //daily special
  Route::get('/v1/DailySpecialView/{token}', 'API\DailySpecialController@view');
  Route::get('/v1/DailySpecialUpcoming/{token}', 'API\DailySpecialController@viewUpcoming');
  Route::get('/v1/DailySpecialDetail/{id}/{token}', 'API\DailySpecialController@detail');
  Route::post('/v1/DailySpecialSearch', 'API\DailySpecialController@search');

  //shop
  Route::get('/v1/shopView/{token}', 'API\ShopController@view');
  Route::get('/v1/shopDetail/{id}/{token}', 'API\ShopController@detail');
  Route::post('/v1/shopSearch', 'API\ShopController@search');
  Route::get('/v1/shopHighlight/{token}', 'API\ShopController@highlight');

  //cart
  Route::post('/v1/cartAdd', 'API\CartController@add');
  Route::get('/v1/cartView/{id}/{token}', 'API\CartController@view');
  Route::post('/v1/cartUpdateByShop', 'API\CartController@updateByShop');
  Route::get('/v1/cartDeleteByProduct/{id}/{product_id}/{order_code}/{token}', 'API\CartController@deleteByProduct');
  Route::get('/v1/cartDeleteByShop/{id}/{shop_id}/{order_code}/{token}', 'API\CartController@deleteByShop');
  Route::get('/v1/cartHistory/{id}/{token}', 'API\CartController@history');
  Route::get('/v1/cartQuantity/{id}/{token}', 'API\CartController@getQuantity');

  //deposit
  Route::get('/v1/depositView/{id}/{token}', 'API\DepositController@view');
  Route::post('/v1/depositDeduct', 'API\DepositController@deduct');
  Route::post('/v1/createDeposit', 'API\PaymentController@createDeposit');
  Route::get('/v1/getDeposit', 'API\PaymentController@getDeposit');
  // Route::post('/v1/notifyPaymentDeposit', 'API\PaymentController@notifyPaymentDeposit');

  //other
  Route::get('/v1/time/{token}', 'API\OtherController@time');
  Route::get('/v1/newsTicker/{token}', 'API\OtherController@newsTicker');
  Route::get('/v1/luckyDrawChance/{id}/{token}', 'API\OtherController@luckyDrawChance');
  Route::get('/v1/luckyDrawWinner/{token}', 'API\OtherController@luckyDrawWinner');
  Route::get('/v1/fbLive/{token}', 'API\OtherController@fbLive');

  //ninja jump
  Route::post('/v1/ninjaJumpCheck', 'API\GamesController@ninjaJumpCheck');
  Route::post('/v1/ninjaJumpResult', 'API\GamesController@ninjaJumpResult');

  //whack a mole
  Route::post('/v1/whackAMoleCheck', 'API\GamesController@whackAMoleCheck');
  Route::post('/v1/whackAMoleResult', 'API\GamesController@whackAMoleResult');
  Route::post('/v1/whackAMoleReset', 'API\GamesController@whackAMoleReset');

  //Nun
  Route::post('/v1/nunCheck', 'API\GamesController@nunCheck');
  Route::post('/v1/nunResult', 'API\GamesController@nunResult');

  //spin and win
  Route::post('/v1/spinAndWinCheck', 'API\GamesController@spinAndWinCheck');
  Route::post('/v1/spinAndWinResult', 'API\GamesController@spinAndWinResult');

  //payment
  Route::post('/v1/createPayment', 'API\PaymentController@createPayment');
  Route::get('/v1/getPayment', 'API\PaymentController@getPayment');
  Route::post('/v1/notifyPaymentCart', 'API\PaymentController@notifyPaymentCart');
  Route::post('/v1/notifyPaymentDeposit', 'API\PaymentController@notifyPaymentDeposit');


  //sell for good -- Genius Girl, i believe you can see this update :P
  Route::get('/v1/sellForGood/{token}', 'API\SellForGoodController@view');
  Route::get('/v1/sellForGoodSales/{token}', 'API\SellForGoodController@sales');

  //discount voucher -- Genius Girl, i believe you can see this update :P
  Route::post('/v1/discountVoucherCheck', 'API\DiscountVoucherController@check');
  Route::post('/v1/discountVoucherDeduct', 'API\DiscountVoucherController@deduct');
  Route::post('/v1/activeVoucher', 'API\DiscountVoucherController@activeVoucher');
  Route::post('/v1/redeemVoucher', 'API\DiscountVoucherController@redeemVoucher');

});
