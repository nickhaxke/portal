<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasFactory;

    /**
     * Internal portal path only: must start with a single "/" (no protocol-relative
     * "//host", no schemes such as javascript: or https:).
     */
    public const CTA_PATTERN = '#^/(?![/\\\\])[A-Za-z0-9\-._~/?=&%\#+]*$#';

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'cta_text',
        'cta_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function imageUrl(): string
    {
        if (empty($this->image_path)) {
            return asset('images/hero/slide-1.jpg');
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        if (str_starts_with($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return asset('storage/'.$this->image_path);
    }

    /**
     * Get validated internal CTA URL or a safe fallback.
     */
    public function safeCtaUrl(): string
    {
        $url = trim((string) $this->cta_url);

        if ($url !== '' && preg_match(self::CTA_PATTERN, $url)) {
            return url($url);
        }

        return route('explore');
    }
}
