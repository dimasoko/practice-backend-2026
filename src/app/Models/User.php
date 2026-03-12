<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password'];

    protected $casts = ['password' => 'hashed'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }
}
