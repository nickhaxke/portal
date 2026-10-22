@extends('layouts.admin')

@section('title', 'Portal System Settings')
@section('header_title', 'Executive Portal Settings & Policies')

@section('content')
<div class="admin-card" style="max-width: 800px;">
    <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Portal Configuration</h3>

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Portal Identity -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label for="portal_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Official Portal Name <span style="color:#c53030;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="portal_name" 
                        name="portal_name" 
                        class="field" 
                        value="{{ old('portal_name', $settings['portal_name']->value ?? 'Executive Intelligence, Leadership & Lifelong Learning Portal') }}" 
                        required 
                        style="width: 100%;"
                    >
                </div>

                <div>
                    <label for="contact_email" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Executive Office Email <span style="color:#c53030;">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="contact_email" 
                        name="contact_email" 
                        class="field" 
                        value="{{ old('contact_email', $settings['contact_email']->value ?? 'executive@portal.internal') }}" 
                        required 
                        style="width: 100%;"
                    >
                </div>
            </div>

            <div>
                <label for="portal_tagline" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Portal Tagline / Monograph Subtitle
                </label>
                <input 
                    type="text" 
                    id="portal_tagline" 
                    name="portal_tagline" 
                    class="field" 
                    value="{{ old('portal_tagline', $settings['portal_tagline']->value ?? 'Curated Strategic Literature for Senior Institutional Leaders') }}" 
                    style="width: 100%;"
                >
            </div>

            <!-- Access & Registration Policy -->
            <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <h4 style="font: 700 15px 'Playfair Display', serif; margin: 0 0 14px; color: var(--navy);">Membership & Reader Governance</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label for="registration_policy" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                            Reader Registration Mode <span style="color:#c53030;">*</span>
                        </label>
                        @php
                            $currPolicy = old('registration_policy', $settings['registration_policy']->value ?? config('portal.registration.policy', 'open'));
                        @endphp
                        <select id="registration_policy" name="registration_policy" class="field" required style="width: 100%;">
                            <option value="open" {{ $currPolicy === 'open' ? 'selected' : '' }}>Open (Direct Registration with Email Verification)</option>
                            <option value="approval_required" {{ $currPolicy === 'approval_required' ? 'selected' : '' }}>Approval Required (Staff Review Before Access)</option>
                            <option value="closed" {{ $currPolicy === 'closed' ? 'selected' : '' }}>Closed (Invitation & Staff Provisioning Only)</option>
                        </select>
                    </div>

                    <div>
                        <label for="max_upload_size_mb" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                            Maximum Upload Size (MB) <span style="color:#c53030;">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="max_upload_size_mb" 
                            name="max_upload_size_mb" 
                            class="field" 
                            value="{{ old('max_upload_size_mb', $settings['max_upload_size_mb']->value ?? '50') }}" 
                            min="5" 
                            max="500" 
                            required 
                            style="width: 100%;"
                        >
                    </div>
                </div>
            </div>

            <!-- Default Document Policies -->
            <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <h4 style="font: 700 15px 'Playfair Display', serif; margin: 0 0 10px; color: var(--navy);">Document Access Standards</h4>
                <div style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--navy); cursor: pointer;">
                        @php
                            $allowDownloadVal = old('default_allow_download', $settings['default_allow_download']->value ?? '0');
                        @endphp
                        <input type="checkbox" name="default_allow_download" value="1" {{ $allowDownloadVal == '1' ? 'checked' : '' }} style="width: 16px; height: 16px;">
                        Default new publications to allow reader downloads (Leave unchecked for strict online-only reading)
                    </label>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="gold" style="padding: 11px 24px; font-size: 14px;">
                    Save Portal Settings &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
