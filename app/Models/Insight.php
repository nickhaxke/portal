<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Insight extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category_name',
        'icon',
        'featured_image',
        'image_alt',
        'excerpt',
        'author_name',
        'read_time',
        'sort_order',
        'is_featured',
        'content',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($insight) {
            if (empty($insight->slug)) {
                $insight->slug = Str::slug($insight->title);
            }
            if (empty($insight->author_name)) {
                $insight->author_name = 'Executive Editorial Board';
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->latest('published_at');
    }

    public function getIconNameAttribute(): string
    {
        $map = [
            '♟' => 'strategy',
            '💡' => 'insights',
            '🛡' => 'shield',
            '🏛' => 'collections',
            '🌐' => 'globe',
            '📊' => 'chart',
        ];

        return $map[$this->icon] ?? ($this->icon ?: 'insights');
    }

    public function featuredImageUrl(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }

        if (str_starts_with($this->featured_image, 'http://') || str_starts_with($this->featured_image, 'https://')) {
            return $this->featured_image;
        }

        if (str_starts_with($this->featured_image, 'images/')) {
            return asset($this->featured_image);
        }

        return asset('storage/'.$this->featured_image);
    }

    public function displayExcerpt(int $limit = 135): string
    {
        if (! empty($this->excerpt)) {
            return Str::limit($this->excerpt, $limit);
        }

        return Str::limit(strip_tags($this->content), $limit);
    }
}
