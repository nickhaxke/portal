<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function getIconNameAttribute(): string
    {
        $map = [
            '♟' => 'strategy',
            '⚖' => 'scale',
            '✧' => 'sparkles',
            '◎' => 'globe',
            '▥' => 'chart',
            '⌘' => 'cog',
            '🏛' => 'collections',
            '🌐' => 'globe',
            '📊' => 'chart',
            '💡' => 'insights',
            '🛡' => 'shield',
        ];

        return $map[$this->icon] ?? ($this->icon ?: 'folder');
    }
}
