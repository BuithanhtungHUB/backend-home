<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailController;


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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/get-all-name', [AuthController::class, 'getAllName']);
Route::get('/auto-update', [OrderController::class, 'autoUpdate']);
Route::get('/get-all', [HouseController::class, 'getAll']);
Route::get('/get-id/{id}', [HouseController::class, 'getById']);
Route::get('/search/{start_date}/{end_date}/{bedroom}/{bathroom}/{price_min}/{price_max}/{address}',[HouseController::class,'search']);
Route::post('/search', [HouseController::class, 'search']);
Route::get('/get-avg/{id}', [ReviewController::class, 'getAvgRate']);
Route::get('/get-review/{id}', [ReviewController::class, 'getReview']);

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/user-profile', [AuthController::class, 'userProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/update-user-profile', [AuthController::class, 'UpdateUserProfile']);
        Route::post('/review/{id}', [ReviewController::class, 'review']);

    });
    Route::prefix('/house')->group(function () {
        Route::post('/create', [HouseController::class, 'create']);
    });

    Route::prefix('/user')->group(function () {
        Route::get('/house-list', [UserController::class, 'getHouseList']);
        Route::post('/update-house/{id}', [UserController::class, 'updateHouse']);
    });
    Route::prefix('/order')->group(function () {
        Route::post('/house-rent/{id}', [OrderController::class, 'houseRent']);
        Route::post('/rent-confirm/{id}', [OrderController::class, 'rentConfirm']);
        Route::get('/get-list', [OrderController::class, 'getListOrderManager']);
        Route::get('/rent-history', [OrderController::class, 'rentHistory']);
        Route::get('/rent-history-house/{id}', [OrderController::class, 'rentHistoryHouse']);
        Route::post('/cancel-rent/{id}', [OrderController::class, 'cancelRent']);
        Route::get('/income-statistics/{id}/{year}', [OrderController::class, 'incomeStatistics']);
    });
});


