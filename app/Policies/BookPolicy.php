<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;
use App\Services\PublicationAccessService;

class BookPolicy
{
    public function __construct(
        protected PublicationAccessService $accessService
    ) {}

    public function view(?User $user, Book $book): bool
    {
        return $this->accessService->canView($user, $book);
    }

    public function read(?User $user, Book $book): bool
    {
        return $this->accessService->canReadOnline($user, $book);
    }

    public function download(?User $user, Book $book): bool
    {
        return $this->accessService->canDownload($user, $book);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Book $book): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->isAdmin();
    }
}
