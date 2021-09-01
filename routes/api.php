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
Route::prefix('/house')->group(function (){
    Route::post('/create',[\App\Http\Controllers\HouseController::class,'create']);
    Route::get('/get-all',[\App\Http\Controllers\HouseController::class,'getAll']);
    Route::get('/get-id/{id}',[\App\Http\Controllers\HouseController::class,'getById']);
});
