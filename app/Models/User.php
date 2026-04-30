<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users_airlink';

    public $timestamps = false;

    protected $fillable = [
        'sacratech_user_id',
        'name',
        'email',
        'photo',
        'airlink_locate_fisrt_entire',
        'share_location',
    ];

    protected $casts = [
        'airlink_locate_fisrt_entire' => 'boolean',
        'share_location' => 'boolean',
    ];

    public function getRememberTokenName()
    {
        return null;
    }

    public function familiesOwned()
    {
        return $this->hasMany(Family::class, 'owner_id');
    }

    public function familyMemberships()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function families()
    {
        return $this->belongsToMany(Family::class, 'family_members')
            ->withPivot(['role']);
    }

    public function circlesOwned()
    {
        return $this->hasMany(Circle::class, 'owner_id');
    }

    public function circleMemberships()
    {
        return $this->hasMany(CircleMember::class);
    }

    public function circleMembers()
    {
        return $this->hasMany(CircleMember::class);
    }

    public function circles()
    {
        return $this->belongsToMany(Circle::class, 'circle_members');
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function safePlaces()
    {
        return $this->hasMany(SafePlace::class);
    }
}
