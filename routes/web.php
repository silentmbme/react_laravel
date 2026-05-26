<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    dd(url('/testing'));
    return response()->json("This is home page");
    return view('welcome');
});
