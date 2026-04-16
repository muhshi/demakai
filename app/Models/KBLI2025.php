<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KBLI2025 extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'KBLI2025';   // case-sensitive
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'contoh_lapangan' => 'array',
    ];

    // MongoDB v5: _id dikelola otomatis oleh driver
}
