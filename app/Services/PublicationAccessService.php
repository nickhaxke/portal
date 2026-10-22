<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicationAccessService
{
    /**
     * Determine if a user or visitor can view publication metadata.
     */
    public function canView(?User $user, Book $book): bool
    {
        if ($book->isPublished()) {
            return true;
        }

        // Draft or Archived only visible to administrators
        return $user !== null && $user->isAdmin();
    }

    /**
     * Determine if a user or visitor can read the publication online.
     */
    public function canReadOnline(?User $user, Book $book): bool
    {
        if (! $this->canView($user, $book)) {
            return false;
        }

        // Admins can always read
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Restricted visibility requires authenticated user
        if ($book->visibility === 'readers_only' || $book->visibility === 'restricted') {
            return $user !== null;
        }

        // Standard published publications are readable
        return $book->isPublished();
    }

    /**
     * Determine if a user is permitted to download the raw document.
     */
    public function canDownload(?User $user, Book $book): bool
    {
        if (! $this->canReadOnline($user, $book)) {
            return false;
        }

        // Admins always have download access for publication management
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Non-admin download permitted ONLY if publication explicitly allows downloads
        return (bool) $book->allow_download && $user !== null;
    }

    /**
     * Generate an authorized, time-limited streaming URL for the PDF.
     */
    public function generateSignedStreamUrl(Book $book): string
    {
        return URL::temporarySignedRoute(
            'books.stream-pdf',
            now()->addMinutes(120),
            ['slug' => $book->slug]
        );
    }

    /**
     * Verify whether an incoming streaming request is authorized.
     */
    public function isStreamAuthorized(Request $request, Book $book): bool
    {
        $user = $request->user();

        // 1. Direct permission check on user
        if ($this->canReadOnline($user, $book)) {
            // Check if route has valid signature OR valid active reading session
            if ($request->hasValidSignature()) {
                return true;
            }

            if ($request->session()->get("authorized_reader_{$book->id}")) {
                return true;
            }

            // If user is logged-in and permitted
            if ($user !== null) {
                return true;
            }
        }

        // If public allowed and signed or session authenticated
        if ($book->isPublished() && $book->visibility === 'public') {
            if ($request->hasValidSignature() || $request->session()->get("authorized_reader_{$book->id}")) {
                return true;
            }
        }

        return false;
    }
}
