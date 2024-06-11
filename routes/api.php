<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BasketballController;

Route::apiResource('players', BasketballController::class);
