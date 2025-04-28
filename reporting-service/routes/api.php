<?php

use App\Http\Controllers\ReportingController;
use Illuminate\Support\Facades\Route;

Route::get('/metric/{metric}', ReportingController::class);
