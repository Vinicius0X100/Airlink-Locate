<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConnection extends Model
{
    protected $fillable = [
        'user_a_id',
        'user_b_id',
        'requested_by',
        'status',
        'share_location',
        'accepted_at',
    ];

    protected $casts = [
        'share_location' => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function userA()
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB()
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }
}
