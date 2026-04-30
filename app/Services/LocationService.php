<?php

namespace App\Services;

use App\Events\LocationUpdated;
use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LocationService
{
    public function __construct(
        private readonly GeofenceService $geofenceService,
    ) {}

    public function ingest(User $user, Device $device, float $lat, float $lng): Location
    {
        return DB::transaction(function () use ($user, $device, $lat, $lng) {
            $location = Location::query()->create([
                'device_id' => $device->id,
                'lat' => $lat,
                'lng' => $lng,
                'created_at' => now(),
            ]);

            $device->forceFill([
                'last_lat' => $lat,
                'last_lng' => $lng,
                'last_seen_at' => now(),
                'is_online' => true,
            ])->save();

            DB::afterCommit(fn () => event(
                new LocationUpdated($user->id, $device->id, $lat, $lng, $location->created_at)
            ));

            $this->geofenceService->process($user, $device, $lat, $lng);

            return $location;
        });
    }
}
