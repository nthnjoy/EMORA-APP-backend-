<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Story extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'journal_texts';
    protected $table = 'journal_texts'; // Explicitly set table to prevent falling back to 'stories'
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'username',
        'nim',
        'content',
        'description',
        'ai_level',
        'ai_label',
        'ai_confidence',
        'ai_red_flag'
    ];
}
