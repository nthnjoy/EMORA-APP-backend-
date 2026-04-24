<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Story extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'stories';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'username',
        'content',
    ];
}
