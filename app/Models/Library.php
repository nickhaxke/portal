<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Library extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'cover_image',
        'is_published',
        'visibility',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Library $library) {
            if (empty($library->slug)) {
                $library->slug = Str::slug($library->name);
                $count = static::withTrashed()->where('slug', 'like', "{$library->slug}%")->count();
                if ($count > 0) {
                    $library->slug .= '-'.($count + 1);
                }
            }
            if (empty($library->icon)) {
                $library->icon = 'library';
            }
            if (empty($library->visibility)) {
                $library->visibility = 'public';
            }
        });
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function publishedBooks(): HasMany
    {
        return $this->hasMany(Book::class)->where('status', 'published');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isRestricted(): bool
    {
        return $this->visibility === 'restricted';
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->cover_image)) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        $cleanPath = preg_replace('#^(public/|storage/)+#', '', ltrim($this->cover_image, '/'));

        return asset('storage/'.$cleanPath);
    }

    public function getIconNameAttribute(): string
    {
        return $this->icon ?: 'library';
    }
}
