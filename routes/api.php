<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CircleController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SafePlaceController;
use App\Models\Alert;
use Illuminate\Http\Request;
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

Route::get('/alerts', function (Request $request) {
    $user = $request->user();

    $alerts = Alert::query()
        ->where('user_id', $user->id)
        ->orderByDesc('created_at')
        ->limit(50)
        ->get();

    $unseen = Alert::query()
        ->where('user_id', $user->id)
        ->whereNull('seen_at')
        ->count();

    return response()->json([
        'unseen_count' => (int) $unseen,
        'alerts' => $alerts->map(fn (Alert $a) => [
            'id' => (int) $a->id,
            'type' => (string) $a->type,
            'message' => (string) $a->message,
            'actor_name' => (string) $a->actor_name,
            'actor_initials' => (string) $a->actor_initials,
            'actor_photo' => $a->actor_photo ? (string) $a->actor_photo : null,
            'date' => $a->created_at ? $a->created_at->format('d/m/y') : now()->format('d/m/y'),
            'seen' => (bool) $a->seen_at,
        ])->values(),
    ]);
})->middleware('auth:sanctum');

Route::post('/alerts/mark-all-seen', function (Request $request) {
    $user = $request->user();

    $count = Alert::query()
        ->where('user_id', $user->id)
        ->whereNull('seen_at')
        ->update(['seen_at' => now()]);

    return response()->json([
        'ok' => true,
        'marked' => (int) $count,
    ]);
})->middleware('auth:sanctum');

Route::post('/location', [LocationController::class, 'store'])->middleware('auth:sanctum');
Route::get('/live', [LocationController::class, 'live'])->middleware('auth:sanctum');
