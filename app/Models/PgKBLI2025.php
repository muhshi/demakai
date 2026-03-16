<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\HasNeighbors;

class PgKBLI2025 extends Model
{
    use HasNeighbors;
    protected $connection = 'pgsql';
    protected $table = 'kbli2025s';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'embedding' => Vector::class,
        'contoh_lapangan' => 'array',
    ];
}
