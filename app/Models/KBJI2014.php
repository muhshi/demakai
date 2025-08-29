<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KBJI2014 extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'KBJI2014';
    public $timestamps = false;

    // Bebasin dulu, nanti kamu rapikan sesuai field sebenarnya
    protected $guarded = [];
}
