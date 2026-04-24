<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Mood extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'moods';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'username',
        'mood_label',
        'perasaan',
        'emosi_kode',
        'title',
        'note',
        'recorded_at',
    ];

    protected $casts = [
        'emosi_kode' => 'integer',
        'recorded_at' => 'datetime',
    ];
}
