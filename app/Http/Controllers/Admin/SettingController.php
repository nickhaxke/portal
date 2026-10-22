<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = PortalSetting::all()->keyBy('key');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'portal_name' => ['required', 'string', 'max:150'],
            'portal_tagline' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:191'],
            'registration_policy' => ['required', 'in:open,approval_required,closed'],
            'default_allow_download' => ['nullable', 'boolean'],
            'max_upload_size_mb' => ['required', 'integer', 'min:5', 'max:500'],
            'homepage_hero_title' => ['nullable', 'string', 'max:255'],
            'homepage_hero_subtitle' => ['nullable', 'string', 'max:500'],
        ]);

        $oldSettings = PortalSetting::pluck('value', 'key')->toArray();

        foreach ($validated as $key => $value) {
            PortalSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string'),
                    'group' => 'general',
                ]
            );
        }

        AuditLogService::log(
            'portal_settings.update',
            null,
            $oldSettings,
            $validated
        );

        return redirect()->route('admin.settings.index')
            ->with('success', 'Portal settings updated successfully.');
    }
}
