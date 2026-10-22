<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->input('action');
        $userId = $request->input('user_id');
        $search = $request->input('search');

        $query = AuditLog::with('user')->latest();

        if ($action) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        $admins = User::whereIn('role', ['superadmin', 'admin', 'executive', 'editor'])->orderBy('name')->get();

        return view('admin.audit-logs.index', compact('logs', 'admins', 'action', 'userId', 'search'));
    }
}
