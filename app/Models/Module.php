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
        'content',
        'icon',
        'points',
        'category',
        'color',
        'content_url',
        'thumbnail_url',
    ];
}
