<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $query = Author::withCount('books')->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('bio', 'like', "%{$search}%");
        }

        $authors = $query->paginate(15)->withQueryString();

        return view('admin.authors.index', compact('authors', 'search'));
    }

    public function create(): View
    {
        return view('admin.authors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string'],
        ]);

        $slug = Str::slug($validated['name']);
        $count = Author::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $author = Author::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'bio' => $validated['bio'] ?? null,
        ]);

        AuditLogService::log('author.create', $author, null, $author->toArray());

        return redirect()->route('admin.authors.index')
            ->with('success', "Author '{$author->name}' added successfully.");
    }

    public function edit(Author $author): View
    {
        $allOtherAuthors = Author::where('id', '!=', $author->id)->orderBy('name')->get();

        return view('admin.authors.edit', compact('author', 'allOtherAuthors'));
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string'],
        ]);

        $oldValues = $author->toArray();

        $author->update([
            'name' => $validated['name'],
            'bio' => $validated['bio'] ?? null,
        ]);

        AuditLogService::log('author.update', $author, $oldValues, $author->fresh()->toArray());

        return redirect()->route('admin.authors.index')
            ->with('success', "Author '{$author->name}' updated successfully.");
    }

    /**
     * Merge duplicate author into a primary author.
     */
    public function merge(Request $request, Author $author): RedirectResponse
    {
        $validated = $request->validate([
            'target_author_id' => ['required', 'exists:authors,id', 'different:id'],
        ]);

        $targetAuthor = Author::findOrFail($validated['target_author_id']);

        return DB::transaction(function () use ($author, $targetAuthor) {
            $booksCount = $author->books()->count();

            // Reassign direct books
            Book::where('author_id', $author->id)->update([
                'author_id' => $targetAuthor->id,
                'author_name' => $targetAuthor->name,
            ]);

            // Reassign pivot authors
            DB::table('book_author')->where('author_id', $author->id)->update([
                'author_id' => $targetAuthor->id,
            ]);

            $oldAuthorName = $author->name;
            $author->delete();

            AuditLogService::log(
                'author.merge',
                $targetAuthor,
                ['merged_author_id' => $author->id, 'merged_author_name' => $oldAuthorName, 'reassigned_books' => $booksCount],
                ['primary_author' => $targetAuthor->toArray()]
            );

            return redirect()->route('admin.authors.index')
                ->with('success', "Successfully merged '{$oldAuthorName}' ({$booksCount} publications) into '{$targetAuthor->name}'.");
        });
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->books()->count() > 0) {
            return back()->with('error', "Cannot delete author '{$author->name}' because they have associated publications. Merge or reassign first.");
        }

        $oldValues = $author->toArray();
        $name = $author->name;
        $author->delete();

        AuditLogService::log('author.delete', $author, $oldValues, null);

        return redirect()->route('admin.authors.index')
            ->with('success', "Author '{$name}' deleted.");
    }
}
