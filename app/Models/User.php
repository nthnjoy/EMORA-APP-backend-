<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'username',
        'password',
        'nim',
        'email',
        'prodi',
        'angkatan',
        'asrama',
        'jenis_kelamin',
        'point',
        'purchased_themes',
        'active_theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];
}