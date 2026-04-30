<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CircleMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'circle_id',
        'user_id',
    ];

    public function circle()
    {
        return $this->belongsTo(Circle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
