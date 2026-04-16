<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KBLI2020 extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'KBLI2020';   // case-sensitive, samakan dengan koleksi
    public $timestamps = false;

    // biar fleksibel
    protected $guarded = [];

    // bantu casting
    protected $casts = [
        'id' => 'integer',
        'kode_4_digit_id' => 'integer',
        'contoh_lapangan' => 'array',
    ];

    // MongoDB v5: _id dikelola otomatis oleh driver, tidak perlu deklarasi manual
}