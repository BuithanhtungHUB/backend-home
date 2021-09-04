<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
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

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::middleware(['auth:api'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('auth.changePassword');
        Route::post('/update-user-profile', [AuthController::class, 'UpdateUserProfile']);
        Route::get('/user-profile', [AuthController::class, 'userProfile']);
    });
    Route::prefix('/house')->group(function () {
        Route::post('/create', [HouseController::class, 'create']);
        Route::get('/get-all', [HouseController::class, 'getAll']);
        Route::get('/get-id/{id}', [HouseController::class, 'getById']);
    });
    Route::prefix('/user')->group(function () {
        Route::get('/house-list', [UserController::class, 'getHouseList']);
        Route::post('/update-house/{id}', [UserController::class, 'updateHouse']);
    });
    Route::prefix('/order')->group(function (){
        Route::post('/house-rent/{id}', [OrderController::class, 'houseRent']);
        Route::post('/rent-confirm/{id}', [OrderController::class, 'rentConfirm']);
        Route::get('/get-list',[OrderController::class,'getList']);
    });
});


