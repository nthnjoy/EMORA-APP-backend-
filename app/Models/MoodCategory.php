<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MoodCategory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'mood_categories';

    protected $fillable = [
        'name',
        'label',
        'description',
        'icon_name',
        'gradient_start',
        'gradient_end',
        'text_color',
    ];
}
