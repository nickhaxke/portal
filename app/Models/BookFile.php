<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'file_path',
        'file_name',
        'file_size',
        'file_hash',
        'version',
        'file_type',
        'disk',
        'page_count',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'page_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
