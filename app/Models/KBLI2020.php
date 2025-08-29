<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KBLI2020 extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'KBLI2020';   // case-sensitive, samakan dengan koleksi
    public $timestamps = false;

    // biar fleksibel
    protected $guarded = [];

    // bantu casting
    protected $casts = [
        'id' => 'integer',
        'kode_4_digit_id' => 'integer',
    ];

    // (opsional, tapi aman) tegaskan primary key ObjectId
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';
}