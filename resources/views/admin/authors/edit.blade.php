@extends('layouts.admin')

@section('title', 'Edit: ' . $author->name)
@section('header_title', 'Edit Author & Merge Control')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.authors.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Authors</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Edit Form -->
    <div class="admin-card" style="margin: 0;">
        <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Author Details</h3>

        <form action="{{ route('admin.authors.update', $author->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Full Name <span style="color:#c53030;">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="field" value="{{ old('name', $author->name) }}" required style="width: 100%;">
                </div>

                <div>
                    <label for="bio" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Biographical Abstract
                    </label>
                    <textarea id="bio" name="bio" rows="5" class="field" style="width: 100%; line-height: 1.5;">{{ old('bio', $author->bio) }}</textarea>
                </div>

                <div style="margin-top: 10px;">
                    <button type="submit" class="gold" style="padding: 10px 20px; font-size: 13.5px;">
                        Update Author &rarr;
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Merge Author Utility -->
    <div class="admin-card" style="margin: 0; background: #fffdf5; border: 1px solid #fae8b8;">
        <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 10px; color: #78350f;">Merge Author Workflow</h4>
        <p style="font-size: 12.5px; color: #92400e; line-height: 1.5; margin-bottom: 16px;">
            If <strong>{{ $author->name }}</strong> is a duplicate entry from Calibre migration or manual input, merge all associated publications into a primary author record below.
        </p>

        <form action="{{ route('admin.authors.merge', $author->id) }}" method="POST" onsubmit="return confirm('MERGE CONFIRMATION: All publications currently attributed to \'{{ addslashes($author->name) }}\' will be reattributed to the target author, and this author profile will be removed. Proceed?')">
            @csrf
            <div style="margin-bottom: 14px;">
                <label for="target_author_id" style="display: block; font-size: 12px; font-weight: 700; color: #78350f; margin-bottom: 6px;">
                    Target Primary Author
                </label>
                <select id="target_author_id" name="target_author_id" class="field" required style="width: 100%; font-size: 13px;">
                    <option value="">Select target author...</option>
                    @foreach($allOtherAuthors as $other)
                        <option value="{{ $other->id }}">
                            {{ $other->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" style="width: 100%; background: #b45309; color: white; border: 0; border-radius: 6px; padding: 10px; font-weight: 600; font-size: 13px; cursor: pointer;">
                Execute Author Merge &rarr;
            </button>
        </form>
    </div>
</div>
@endsection
