<?php

use App\Models\Circle;
use App\Models\Family;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

Broadcast::channel('family.{familyId}', function ($user, int $familyId) {
    return Family::query()
        ->whereKey($familyId)
        ->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
        })
        ->exists();
});

Broadcast::channel('circle.{circleId}', function ($user, int $circleId) {
    return Circle::query()
        ->whereKey($circleId)
        ->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
        })
        ->exists();
});
