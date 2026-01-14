<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KBLI2025 extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'KBLI2025';   // case-sensitive
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'contoh_lapangan' => 'array',
    ];

    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
