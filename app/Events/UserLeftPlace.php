<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UserLeftPlace implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $safePlaceId,
        public readonly string $safePlaceName,
        public readonly int $deviceId,
        public readonly float $lat,
        public readonly float $lng,
        public readonly \DateTimeInterface $occurredAt,
    ) {}

    public function broadcastOn(): array
    {
        $familyIds = DB::table('family_members')->where('user_id', $this->userId)->pluck('family_id')->all();
        $circleIds = DB::table('circle_members')->where('user_id', $this->userId)->pluck('circle_id')->all();

        return array_values(array_merge(
            [new PrivateChannel('user.'.$this->userId)],
            array_map(fn ($id) => new PrivateChannel('family.'.$id), $familyIds),
            array_map(fn ($id) => new PrivateChannel('circle.'.$id), $circleIds),
        ));
    }

    public function broadcastAs(): string
    {
        return 'UserLeftPlace';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'safe_place_id' => $this->safePlaceId,
            'safe_place_name' => $this->safePlaceName,
            'device_id' => $this->deviceId,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
