<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Web\InviteController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\LoginController;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\SafePlace;
use App\Models\User;
use App\Models\UserConnection;
use App\Services\InvitationService;
use App\Services\LocationService;
use App\Services\SacratechAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => view('pages.landing'))->name('landing');
    Route::get('/sobre', fn () => view('pages.sobre'));
    Route::get('/como-funciona', fn () => view('pages.como-funciona'));

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->middleware('throttle:login')->name('login.perform');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/termos-de-uso', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacidade', [LegalController::class, 'privacy'])->name('privacy');

Route::get('/dashboard', function () {
    return view('app.dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/api/devices/locations', [LocationController::class, 'devicesLocations'])->name('devices.locations');

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
    })->name('alerts.index');

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
    })->name('alerts.mark_all_seen');

    Route::post('/me/share-location', function (Request $request) {
        $data = $request->validate([
            'share_location' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $share = (bool) $data['share_location'];

        $user->forceFill([
            'share_location' => $share,
        ])->save();

        if (! $share) {
            Device::query()
                ->where('user_id', $user->id)
                ->update(['is_online' => false]);
        }

        return response()->json([
            'ok' => true,
            'share_location' => $share,
        ]);
    })->name('me.share_location');

    Route::post('/location/ping', function (Request $request) {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();

        if (! $user->share_location) {
            Device::query()
                ->where('user_id', $user->id)
                ->update(['is_online' => false]);

            return response()->json([
                'ok' => true,
                'paused' => true,
            ]);
        }

        $device = Device::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Web',
            ],
            [],
        );

        $location = app(LocationService::class)->ingest(
            $user,
            $device,
            (float) $data['lat'],
            (float) $data['lng'],
        );

        return response()->json([
            'ok' => true,
            'device_id' => $device->id,
            'created_at' => $location->created_at?->format(DATE_ATOM),
        ]);
    })->middleware('throttle:120,1')->name('location.ping');

    Route::post('/me/photo', function (Request $request) {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $path = $data['photo']->storePublicly('user-photos', 'public');

        $request->user()->forceFill([
            'photo' => $path,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'photo' => $path,
                'url' => asset('storage/'.$path),
            ]);
        }

        return back();
    })->name('me.photo');

    Route::get('/connections/{user}/profile', function (Request $request, User $user) {
        $me = $request->user();

        $connected = UserConnection::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($me, $user) {
                $q->where(function ($q2) use ($me, $user) {
                    $q2->where('user_a_id', $me->id)->where('user_b_id', $user->id);
                })->orWhere(function ($q2) use ($me, $user) {
                    $q2->where('user_b_id', $me->id)->where('user_a_id', $user->id);
                });
            })
            ->exists();

        abort_unless($connected, 403);

        $fullName = (string) ($user->name ?? 'Usuário');
        if ($user->sacratech_user_id) {
            $s = app(SacratechAuthService::class)->fetchUserById((int) $user->sacratech_user_id);
            if ($s) {
                $first = trim((string) ($s->nome ?? ''));
                $last = trim((string) ($s->sobrenome ?? ''));
                $n = trim($first.' '.$last);
                if ($n !== '') {
                    $fullName = $n;
                }
            }
        }

        $initials = '?';
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? ($parts[count($parts) - 1] ?? '') : '';
        $initials = trim(mb_substr($first, 0, 1).mb_substr($last, 0, 1));
        if ($initials === '') {
            $initials = mb_strtoupper(mb_substr($fullName, 0, 2));
        } else {
            $initials = mb_strtoupper($initials);
        }

        $circles = Circle::query()
            ->whereHas('users', fn ($q) => $q->where('users_airlink.id', $me->id))
            ->whereHas('users', fn ($q) => $q->where('users_airlink.id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Circle $c) => (string) $c->name)
            ->values();

        $families = Family::query()
            ->whereHas('users', fn ($q) => $q->where('users_airlink.id', $me->id))
            ->whereHas('users', fn ($q) => $q->where('users_airlink.id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Family $f) => (string) $f->name)
            ->values();

        $places = SafePlace::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SafePlace $p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'icon' => $p->icon ? (string) $p->icon : null,
                'address' => $p->address ? (string) $p->address : null,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'radius' => (int) $p->radius,
            ])
            ->values();

        return response()->json([
            'user' => [
                'id' => (int) $user->id,
                'full_name' => $fullName,
                'initials' => $initials,
                'photo_url' => $user->photo ? asset('storage/'.$user->photo) : null,
            ],
            'shared' => [
                'circles' => $circles,
                'families' => $families,
            ],
            'safe_places' => $places,
        ]);
    })->name('connections.profile');

    Route::get('/safe-places', function (Request $request) {
        $places = SafePlace::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SafePlace $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'icon' => $p->icon,
                'address' => $p->address,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'radius' => (int) $p->radius,
            ])
            ->values();

        return response()->json([
            'safe_places' => $places,
        ]);
    })->name('safe_places.index');

    Route::post('/safe-places', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:25', 'max:2000'],
        ]);

        $place = SafePlace::query()->create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'address' => $data['address'],
            'latitude' => (float) $data['lat'],
            'longitude' => (float) $data['lng'],
            'radius' => (int) $data['radius'],
            'created_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'safe_place' => [
                    'id' => $place->id,
                    'name' => $place->name,
                    'icon' => $place->icon,
                    'address' => $place->address,
                    'lat' => (float) $place->latitude,
                    'lng' => (float) $place->longitude,
                    'radius' => (int) $place->radius,
                ],
            ], 201);
        }

        return back();
    })->name('safe_places.store');

    Route::post('/families', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $family = Family::query()->create([
            'name' => $data['name'],
            'owner_id' => $request->user()->id,
        ]);

        FamilyMember::query()->updateOrCreate(
            [
                'family_id' => $family->id,
                'user_id' => $request->user()->id,
            ],
            [
                'role' => 'owner',
            ],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'family' => [
                    'id' => $family->id,
                    'name' => $family->name,
                    'users_count' => 1,
                ],
            ], 201);
        }

        return back();
    })->name('families.store');

    Route::post('/circles', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $circle = Circle::query()->create([
            'name' => $data['name'],
            'owner_id' => $request->user()->id,
        ]);

        CircleMember::query()->updateOrCreate(
            [
                'circle_id' => $circle->id,
                'user_id' => $request->user()->id,
            ],
            [],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'circle' => [
                    'id' => $circle->id,
                    'name' => $circle->name,
                    'users_count' => 1,
                ],
            ], 201);
        }

        return back();
    })->name('circles.store');

    Route::post('/families/{family}/invite', function (Request $request, Family $family) {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $request->user();

        $isMember = (int) $family->owner_id === (int) $user->id || $family->members()->where('user_id', $user->id)->exists();
        if (! $isMember) {
            abort(403);
        }

        $service = app(InvitationService::class);

        $email = mb_strtolower(trim((string) $data['email']));
        $invitee = $service->resolveInviteeByEmail($email);
        if ((int) $invitee->id === (int) $user->id) {
            return response()->json([
                'message' => 'Você não pode convidar você mesmo.',
            ], 422);
        }

        $created = $service->createInvitation([
            'type' => 'family',
            'inviter_user_id' => $user->id,
            'invitee_user_id' => $invitee->id,
            'invitee_email' => $email,
            'family_id' => $family->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'url' => url('/invite/'.$created['token']),
        ], 201);
    })->name('families.invite');

    Route::post('/circles/{circle}/invite', function (Request $request, Circle $circle) {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $request->user();

        $isMember = (int) $circle->owner_id === (int) $user->id || $circle->members()->where('user_id', $user->id)->exists();
        if (! $isMember) {
            abort(403);
        }

        $service = app(InvitationService::class);

        $email = mb_strtolower(trim((string) $data['email']));
        $invitee = $service->resolveInviteeByEmail($email);
        if ((int) $invitee->id === (int) $user->id) {
            return response()->json([
                'message' => 'Você não pode convidar você mesmo.',
            ], 422);
        }

        $created = $service->createInvitation([
            'type' => 'circle',
            'inviter_user_id' => $user->id,
            'invitee_user_id' => $invitee->id,
            'invitee_email' => $email,
            'circle_id' => $circle->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'url' => url('/invite/'.$created['token']),
        ], 201);
    })->name('circles.invite');

    Route::post('/connections/invite', function (Request $request) {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $service = app(InvitationService::class);

        $invitee = $service->resolveInviteeByEmail((string) $data['email']);
        if ($invitee->id === $request->user()->id) {
            return response()->json([
                'message' => 'Você não pode convidar você mesmo.',
            ], 422);
        }

        $created = $service->createInvitation([
            'type' => 'connection',
            'inviter_user_id' => $request->user()->id,
            'invitee_user_id' => $invitee->id,
            'invitee_email' => mb_strtolower(trim((string) $data['email'])),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'url' => url('/invite/'.$created['token']),
        ], 201);
    })->name('connections.invite');

    Route::post('/legal/accept', [LegalController::class, 'accept'])->name('legal.accept');
    Route::get('/localizacao-necessaria', [LegalController::class, 'locationRequired'])->name('location.required');
    Route::get('/invite/{token}', [InviteController::class, 'show'])->name('invite.show');
    Route::post('/invite/{token}/accept', [InviteController::class, 'accept'])->name('invite.accept');
    Route::post('/invite/{token}/decline', [InviteController::class, 'decline'])->name('invite.decline');
});
