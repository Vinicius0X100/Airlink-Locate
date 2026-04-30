<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CircleController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SafePlaceController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::post('/me/photo', [AuthController::class, 'uploadPhoto'])->middleware('auth:sanctum');

Route::post('/families', [FamilyController::class, 'store'])->middleware('auth:sanctum');
Route::post('/families/{family}/invite', [FamilyController::class, 'invite'])->middleware('auth:sanctum');
Route::get('/families', [FamilyController::class, 'index'])->middleware('auth:sanctum');

Route::post('/circles', [CircleController::class, 'store'])->middleware('auth:sanctum');
Route::post('/circles/{circle}/members', [CircleController::class, 'addMember'])->middleware('auth:sanctum');
Route::get('/circles', [CircleController::class, 'index'])->middleware('auth:sanctum');

Route::get('/invitations', [InvitationController::class, 'index'])->middleware('auth:sanctum');
Route::post('/invitations', [InvitationController::class, 'store'])->middleware('auth:sanctum');
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->middleware('auth:sanctum');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->middleware('auth:sanctum');
Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline'])->middleware('auth:sanctum');
Route::delete('/invitations/{invitation}', [InvitationController::class, 'revoke'])->middleware('auth:sanctum');

Route::get('/safe-places', [SafePlaceController::class, 'index'])->middleware('auth:sanctum');
Route::post('/safe-places', [SafePlaceController::class, 'store'])->middleware('auth:sanctum');
Route::delete('/safe-places/{safePlace}', [SafePlaceController::class, 'destroy'])->middleware('auth:sanctum');

Route::post('/location', [LocationController::class, 'store'])->middleware('auth:sanctum');
Route::get('/live', [LocationController::class, 'live'])->middleware('auth:sanctum');
