<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Vector;

class PgKBJI2014 extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'kbji2014s'; // Assuming standard Laravel naming convention
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'embedding' => Vector::class,
        'contoh_lapangan' => 'array',
    ];
}
