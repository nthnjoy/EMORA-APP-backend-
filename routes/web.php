<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;



Route::post('/jwt-api/do-auth', [AuthController::class, 'login']);
Route::get('/', function () {
    return view('welcome');
});
