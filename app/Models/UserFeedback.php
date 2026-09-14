<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
    use HasFactory;

    protected $table = 'user_feedbacks';
    protected $guarded = [];

    public static function getCategoryOptions(): array
    {
        return [
            'saran_fitur' => '💡 Saran Fitur Baru',
            'koreksi_hasil' => '🔍 Koreksi Hasil Pencarian',
            'kendala_bug' => '🐞 Laporan Kendala / Bug',
            'umum' => '💬 Masukan Umum',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'baru' => 'Baru (Belum Ditinjau)',
            'ditinjau' => 'Sedang Ditinjau',
            'diterapkan' => 'Sudah Diterapkan',
            'diarsipkan' => 'Diarsipkan',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::getCategoryOptions()[$this->category] ?? '💬 Masukan Umum';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'baru' => 'warning',
            'ditinjau' => 'info',
            'diterapkan' => 'success',
            'diarsipkan' => 'gray',
            default => 'secondary',
        };
    }
}
