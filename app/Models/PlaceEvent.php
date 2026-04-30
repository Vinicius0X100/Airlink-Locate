<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'safe_place_id',
        'type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function safePlace()
    {
        return $this->belongsTo(SafePlace::class);
    }
}
