<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'type',
        'message',
        'actor_name',
        'actor_initials',
        'actor_photo',
        'group_id',
        'group_name',
        'created_at',
        'seen_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'seen_at' => 'datetime',
    ];
}

