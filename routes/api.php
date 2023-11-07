<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('ping/{hostname}', [\App\Http\Controllers\Api\PingController::class, 'show']);
Route::get('mtr/{hostname?}', [\App\Http\Controllers\Api\MtrController::class, 'show']);
Route::get('asn/{asn}', \App\Http\Controllers\Api\AsnController::class);
Route::get('traceroute/{hostname}', [\App\Http\Controllers\Api\TracerouteController::class, 'show']);
Route::get('ip', \App\Http\Controllers\Api\IpController::class);
