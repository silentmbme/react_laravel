<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
   Route::get('/users',[UsersController::class,'index']); 
});

Route::get('testing',function(){
    return response()->json("LFKdslkfj");
});