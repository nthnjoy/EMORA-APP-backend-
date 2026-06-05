<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Module extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'modules';

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'content',
        'icon',
        'points',
        'reward_point',
        'category',
        'kategori',
        'color',
        'status',
        'content_url',
        'thumbnail',      // field yg dipakai TA-KEL-12
        'thumbnail_url',  // field lama
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
