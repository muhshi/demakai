<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kbli2025Hierarchy extends Model
{
    use HasFactory;

    protected $table = 'kbli2025_hierarchies';

    protected $fillable = [
        'level',
        'kode',
        'judul',
        'deskripsi',
        'parent_kode',
    ];

    public function children()
    {
        return $this->hasMany(Kbli2025Hierarchy::class, 'parent_kode', 'kode');
    }

    public function parent()
    {
        return $this->belongsTo(Kbli2025Hierarchy::class, 'parent_kode', 'kode');
    }
}
