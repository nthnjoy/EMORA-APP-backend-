<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Feeling extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'feelings';

    protected $fillable = [
        'user_id',
        'nim',
        'username',
        'mood_id',
        'feeling_id',
        'feeling_name',
        'feeling_code',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];
}
