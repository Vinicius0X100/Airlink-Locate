<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
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
        return $this->hasMany(FamilyMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'family_members')
            ->withPivot(['role']);
    }
}
