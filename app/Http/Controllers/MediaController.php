<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Fallback route to serve public media and cover artwork when the web server's
     * public/storage symbolic link is missing, disabled, or unconfigured on live hosting.
     */
    public function servePublicStorage(Request $request, string $path)
    {
        // Sanitize path against directory traversal
        $cleanPath = str_replace(['..', "\0"], '', $path);
        $cleanPath = ltrim($cleanPath, '/');

        if (empty($cleanPath) || ! Storage::disk('public')->exists($cleanPath)) {
            abort(404, 'Requested public asset not found.');
        }

        $fullPath = Storage::disk('public')->path($cleanPath);

        // Security check: ensure path is within public storage root
        $publicRoot = realpath(Storage::disk('public')->path(''));
        $realFullPath = realpath($fullPath);
        if ($publicRoot === false || $realFullPath === false) {
            abort(404, 'Asset not found.');
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', strtolower($publicRoot)), '/');
        $normalizedPath = str_replace('\\', '/', strtolower($realFullPath));
        if (! str_starts_with($normalizedPath, $normalizedRoot)) {
            abort(403, 'Forbidden asset access.');
        }

        $mimeType = match (strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            default => mime_content_type($fullPath) ?: 'application/octet-stream',
        };

        $response = new BinaryFileResponse($realFullPath, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setAutoEtag();
        $response->isNotModified($request);

        return $response;
    }
}
