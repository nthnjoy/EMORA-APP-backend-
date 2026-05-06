<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class DailyCheckin extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'daily_checkins';

    protected $fillable = [
        'user_id',
        'nim',
        'username',
        'mood_id',
        'feeling_id',
        'mood_label',
        'perasaan',
        'recorded_at',
    ];

    protected $casts = [
        'mood_id' => 'integer',
        'feeling_id' => 'integer',
        'recorded_at' => 'datetime',
    ];
}
