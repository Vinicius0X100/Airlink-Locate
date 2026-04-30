<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(CircleMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'circle_members');
    }
}
