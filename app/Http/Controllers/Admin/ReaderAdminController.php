<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReaderAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $verified = $request->input('verified');

        $query = User::where('role', 'reader')
            ->withCount(['readingProgress', 'bookmarks', 'savedBooks'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position_title', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($verified === '1') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verified === '0') {
            $query->whereNull('email_verified_at');
        }

        $readers = $query->paginate(15)->withQueryString();

        return view('admin.readers.index', compact('readers', 'search', 'status', 'verified'));
    }

    public function show(User $reader): View
    {
        abort_unless($reader->role === 'reader', 404);

        $reader->load([
            'readingProgress' => fn ($q) => $q->with('book')->latest('last_read_at')->take(10),
            'bookmarks' => fn ($q) => $q->with('book')->latest()->take(10),
            'savedBooks' => fn ($q) => $q->with('book')->latest()->take(10),
        ]);

        return view('admin.readers.show', compact('reader'));
    }

    public function toggleStatus(Request $request, User $reader): RedirectResponse
    {
        abort_unless($reader->role === 'reader', 403);

        $oldStatus = $reader->status ?? 'active';
        $newStatus = $oldStatus === 'active' ? 'suspended' : 'active';

        $reader->status = $newStatus;
        $reader->save();

        AuditLogService::log(
            'reader.toggle_status',
            $reader,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );

        return back()->with('success', "Reader '{$reader->name}' status changed to '{$newStatus}'.");
    }

    public function verifyEmail(Request $request, User $reader): RedirectResponse
    {
        abort_unless($reader->role === 'reader', 403);

        if (! $reader->hasVerifiedEmail()) {
            $reader->markEmailAsVerified();

            AuditLogService::log(
                'reader.manual_verify_email',
                $reader,
                ['email_verified_at' => null],
                ['email_verified_at' => now()->toDateTimeString()]
            );

            return back()->with('success', "Email for '{$reader->name}' manually verified.");
        }

        return back()->with('info', 'Reader email is already verified.');
    }
}
