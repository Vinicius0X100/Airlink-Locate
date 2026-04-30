<?php

namespace App\Services;

use App\Events\UserEnteredPlace;
use App\Events\UserLeftPlace;
use App\Models\Device;
use App\Models\PlaceEvent;
use App\Models\SafePlace;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GeofenceService
{
    public function process(User $user, Device $device, float $lat, float $lng): void
    {
        $places = SafePlace::query()
            ->where('user_id', $user->id)
            ->get();

        foreach ($places as $place) {
            $insideNow = $this->isInside($lat, $lng, (float) $place->latitude, (float) $place->longitude, (int) $place->radius);

            $lastEvent = PlaceEvent::query()
                ->where('user_id', $user->id)
                ->where('safe_place_id', $place->id)
                ->orderByDesc('id')
                ->first();

            $insideBefore = $lastEvent ? $lastEvent->type === 'enter' : false;

            if ($insideNow === $insideBefore) {
                continue;
            }

            $type = $insideNow ? 'enter' : 'leave';

            $created = PlaceEvent::query()->create([
                'user_id' => $user->id,
                'safe_place_id' => $place->id,
                'type' => $type,
                'created_at' => now(),
            ]);

            DB::afterCommit(function () use ($type, $user, $place, $device, $lat, $lng, $created) {
                if ($type === 'enter') {
                    event(new UserEnteredPlace($user->id, $place->id, $place->name, $device->id, $lat, $lng, $created->created_at));
                } else {
                    event(new UserLeftPlace($user->id, $place->id, $place->name, $device->id, $lat, $lng, $created->created_at));
                }
            });
        }
    }

    private function isInside(float $lat, float $lng, float $placeLat, float $placeLng, int $radiusMeters): bool
    {
        return $this->distanceMeters($lat, $lng, $placeLat, $placeLng) <= $radiusMeters;
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
