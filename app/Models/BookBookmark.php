<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookBookmark extends Model
{
    use HasFactory;

    protected $table = 'book_bookmarks';

    protected $fillable = [
        'user_id',
        'book_id',
        'page_number',
        'title',
        'note',
    ];

    protected $casts = [
        'page_number' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
