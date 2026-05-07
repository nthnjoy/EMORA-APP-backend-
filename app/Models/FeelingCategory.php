<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FeelingCategory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'feeling_categories';

    protected $fillable = [
        'mood_name',
        'name',
        'description',
    ];
}
