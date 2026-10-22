<?php

namespace App\Services;

use App\Models\HeroSlide;
use App\Models\Insight;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Centralised handling of public editorial imagery (hero slides, insight artwork).
 *
 * Editorial images live on the "public" disk; private publication PDFs are kept
 * on the "local" disk and are never touched by this service.
 */
class EditorialImageService
{
    public const DISK = 'public';

    /** Validation rules shared by all editorial image uploads. */
    public static function rules(bool $required, int $minWidth, int $minHeight): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpeg,jpg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:5120',
            "dimensions:min_width={$minWidth},min_height={$minHeight},max_width=6000,max_height=6000",
        ];
    }

    /** Store an upload under a safe, generated filename. */
    public function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $name = now()->format('Ymd').'-'.Str::random(24).'.'.$extension;

        return $file->storeAs($directory, $name, self::DISK);
    }

    /**
     * Store a replacement first, then remove the previous file only if
     * the new upload succeeded and no other record still references it.
     */
    public function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        $newPath = $this->store($file, $directory);

        if ($oldPath && $oldPath !== $newPath) {
            $this->deleteIfUnreferenced($oldPath, 1);
        }

        return $newPath;
    }

    /**
     * Delete a file only when it is referenced by at most $allowedReferences
     * records (i.e. the record currently being changed/deleted).
     */
    public function deleteIfUnreferenced(?string $path, int $allowedReferences = 1): bool
    {
        if (empty($path) || str_starts_with($path, 'http') || str_starts_with($path, 'images/')) {
            return false;
        }

        if ($this->referenceCount($path) > $allowedReferences) {
            return false;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->delete($path);
        }

        return false;
    }

    public function referenceCount(string $path): int
    {
        return HeroSlide::where('image_path', $path)->count()
            + Insight::where('featured_image', $path)->count();
    }
}
