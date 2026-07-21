<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShareController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/share/product/{slug}', [ShareController::class, 'product']);
