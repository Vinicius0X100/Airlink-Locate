<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafePlace extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'latitude',
        'longitude',
        'radius',
        'address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(PlaceEvent::class);
    }
}
