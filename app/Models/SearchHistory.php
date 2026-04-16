<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'results_count',
        'detected_type',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected $casts = [
        'results_count' => 'integer',
    ];
}
