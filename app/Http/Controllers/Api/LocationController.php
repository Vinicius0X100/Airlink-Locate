<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CircleMember;
use App\Models\Device;
use App\Models\FamilyMember;
use App\Models\User;
use App\Models\UserConnection;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
    ) {}

    public function devicesLocations(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $familyIds = $user->familyMembers()->pluck('family_id');
        $circleIds = $user->circleMembers()->pluck('circle_id');

        $connectedUserIds = UserConnection::query()
            ->where('status', 'accepted')
            ->where('share_location', true)
            ->where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })
            ->get()
            ->map(function (UserConnection $c) use ($user) {
                return $c->user_a_id === $user->id ? $c->user_b_id : $c->user_a_id;
            });

        $userIds = Collection::make([$user->id])
            ->merge(FamilyMember::query()->whereIn('family_id', $familyIds)->pluck('user_id'))
            ->merge(CircleMember::query()->whereIn('circle_id', $circleIds)->pluck('user_id'))
            ->merge($connectedUserIds)
            ->unique()
            ->values();

        $devices = Device::query()
            ->whereIn('user_id', $userIds)
            ->orderByDesc('last_seen_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($list) => $list->first());

        $onlineThresholdSeconds = 180;

        $rows = User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->map(function (User $u) use ($devices, $onlineThresholdSeconds) {
                /** @var Device|null $device */
                $device = $devices->get($u->id);

                if (! $device) {
                    return null;
                }

                $lastSeenAt = $device->last_seen_at;
                $isOnline = (bool) ($lastSeenAt && $lastSeenAt->gt(now()->subSeconds($onlineThresholdSeconds)));

                return [
                    'id' => $device->id,
                    'user_id' => $u->id,
                    'name' => (string) $u->name,
                    'device_name' => (string) ($device->name ?: ''),
                    'photo' => $u->photo
                        ? (str_starts_with((string) $u->photo, 'http') ? (string) $u->photo : asset('storage/'.$u->photo))
                        : null,
                    'lat' => is_null($device->last_lat) ? null : (float) $device->last_lat,
                    'lng' => is_null($device->last_lng) ? null : (float) $device->last_lng,
                    'last_seen_at' => $lastSeenAt?->format(DATE_ATOM),
                    'is_online' => $isOnline,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'devices' => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required', 'integer', 'min:1'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();

        $device = Device::query()
            ->whereKey((int) $data['device_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $location = $this->locationService->ingest(
            $user,
            $device,
            (float) $data['lat'],
            (float) $data['lng'],
        );

        return response()->json([
            'location' => $location,
            'device' => $device->fresh(),
        ]);
    }

    public function live(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $familyIds = $user->familyMembers()->pluck('family_id');
        $circleIds = $user->circleMembers()->pluck('circle_id');

        $connectedUserIds = UserConnection::query()
            ->where('status', 'accepted')
            ->where('share_location', true)
            ->where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })
            ->get()
            ->map(function (UserConnection $c) use ($user) {
                return $c->user_a_id === $user->id ? $c->user_b_id : $c->user_a_id;
            });

        $userIds = Collection::make([$user->id])
            ->merge(FamilyMember::query()->whereIn('family_id', $familyIds)->pluck('user_id'))
            ->merge(CircleMember::query()->whereIn('circle_id', $circleIds)->pluck('user_id'))
            ->merge($connectedUserIds)
            ->unique()
            ->values();

        $devices = Device::query()
            ->whereIn('user_id', $userIds)
            ->orderByDesc('last_seen_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($list) => $list->first());

        $onlineThresholdSeconds = 180;

        $rows = User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->map(function (User $u) use ($devices, $onlineThresholdSeconds) {
                /** @var Device|null $device */
                $device = $devices->get($u->id);

                $lastSeenAt = $device?->last_seen_at;
                $isOnline = (bool) ($lastSeenAt && $lastSeenAt->gt(now()->subSeconds($onlineThresholdSeconds)));

                return [
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'photo' => $u->photo ? asset('storage/'.$u->photo) : null,
                    'device_id' => $device?->id,
                    'lat' => $device?->last_lat,
                    'lng' => $device?->last_lng,
                    'last_seen_at' => $lastSeenAt?->format(DATE_ATOM),
                    'is_online' => $isOnline,
                ];
            })
            ->values();

        return response()->json([
            'users' => $rows,
        ]);
    }
}
