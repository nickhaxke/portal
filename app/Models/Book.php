<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'library_id',
        'title',
        'subtitle',
        'slug',
        'publication_type',
        'category_id',
        'author_id',
        'author_name',
        'publisher',
        'language',
        'edition',
        'series',
        'series_number',
        'publication_year',
        'published_year',
        'isbn',
        'isbn13',
        'doi',
        'description',
        'citation_text',
        'cover_bg',
        'cover_fg',
        'cover_image',
        'is_featured',
        'pages_count',
        'status',
        'allow_download',
        'visibility',
        'calibre_id',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'allow_download' => 'boolean',
        'pages_count' => 'integer',
        'publication_year' => 'integer',
        'published_year' => 'integer',
        'series_number' => 'decimal:2',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('publication_type', $type);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function canDownload(): bool
    {
        return (bool) $this->allow_download;
    }

    public function formattedPublicationType(): string
    {
        return match ($this->publication_type) {
            'research_paper' => 'Research Paper',
            'report' => 'Executive Report',
            'policy_document' => 'Policy Document',
            'thesis' => 'Thesis / Dissertation',
            default => 'Book / Monograph',
        };
    }

    public function formattedCitation(): string
    {
        if (! empty($this->citation_text)) {
            return $this->citation_text;
        }

        $year = $this->publication_year ?: $this->published_year ?: date('Y');
        $author = $this->author_name ?: 'Executive Editorial Board';
        $title = $this->title;
        $publisher = $this->publisher ?: 'Executive Intelligence Press';

        $citation = "{$author} ({$year}). {$title}. {$publisher}.";
        if (! empty($this->doi)) {
            $citation .= " https://doi.org/{$this->doi}";
        }

        return $citation;
    }

    protected static function booted()
    {
        static::creating(function ($book) {
            if (empty($book->slug)) {
                $book->slug = Str::slug($book->title).'-'.Str::random(5);
            }
            if (empty($book->publication_type)) {
                $book->publication_type = 'book';
            }
            if (empty($book->status)) {
                $book->status = 'published';
            }
            if ($book->status === 'published' && empty($book->published_at)) {
                $book->published_at = now();
            }
        });
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'book_tag')->withTimestamps();
    }

    public function syncTags(array|string|null $tagInput): void
    {
        if (empty($tagInput)) {
            $this->tags()->detach();

            return;
        }

        $tagNames = is_array($tagInput)
            ? $tagInput
            : array_filter(array_map('trim', explode(',', $tagInput)));

        $tagIds = [];
        foreach ($tagNames as $name) {
            if ($name === '') {
                continue;
            }
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $this->tags()->sync($tagIds);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_category')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_book')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author')
            ->withPivot('role', 'sort_order')
            ->withTimestamps()
            ->orderBy('book_author.sort_order');
    }

    public function contributors(): BelongsToMany
    {
        return $this->authors();
    }

    public function files(): HasMany
    {
        return $this->hasMany(BookFile::class);
    }

    public function primaryPdf(): HasOne
    {
        return $this->hasOne(BookFile::class)
            ->where('file_type', 'pdf')
            ->where('is_active', true)
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('file_type', 'pdf')->where('is_active', true);
            });
    }

    public function savedRecords(): HasMany
    {
        return $this->hasMany(SavedBook::class);
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(BookBookmark::class);
    }

    public function userProgress(?int $userId = null)
    {
        $uid = $userId ?: auth()->id();
        if (! $uid) {
            return null;
        }

        return $this->readingProgress()->where('user_id', $uid)->first();
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->cover_image)) {
            return null;
        }

        $path = $this->cover_image;

        // If stored as full URL, return directly
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Clean redundant prefixes e.g. storage/, public/, /
        $cleanPath = preg_replace('#^(public/|storage/)+#', '', ltrim($path, '/'));

        return asset('storage/'.$cleanPath);
    }

    public function getPublicationTypeLabelAttribute(): string
    {
        $types = config('portal.publication_types', []);

        return $types[$this->publication_type]['name'] ?? ucwords(str_replace('_', ' ', $this->publication_type ?? 'Book'));
    }

    public function getPublicationTypeBadgeAttribute(): string
    {
        $types = config('portal.publication_types', []);

        return $types[$this->publication_type]['badge'] ?? ucwords(str_replace('_', ' ', $this->publication_type ?? 'Book'));
    }

    public function getPublicationTypeIconAttribute(): string
    {
        $types = config('portal.publication_types', []);

        return $types[$this->publication_type]['icon'] ?? 'book';
    }
}
