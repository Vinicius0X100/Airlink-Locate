<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'token_hash',
        'type',
        'inviter_user_id',
        'invitee_user_id',
        'invitee_email',
        'family_id',
        'circle_id',
        'status',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_user_id');
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }
}
