<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class JSONParse extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'Document';   // case-sensitive, samakan dengan koleksi
    public $timestamps = false;

    // biar fleksibel
    protected $guarded = [];

    protected $fillable = [
        'filename',
        'source_type',   // publikasi | paper_penelitian | data
        'title',
        'authors',
        'year',
        'doi',
        'tags',
        'metadata',      // info tambahan (num_pages, chars_count, dll)
        'similarity_score',
        'chunks',        // array chunk teks + embedding
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'chunks'   => 'array',
        'tags'     => 'array',
    ];

}
