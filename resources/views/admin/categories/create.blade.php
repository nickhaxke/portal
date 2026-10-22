@extends('layouts.admin')

@section('title', 'Add Discipline / Category')
@section('header_title', 'Create Knowledge Discipline')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.categories.index') }}" class="plain" style="font-size: 13px; color: var(--navy); text-decoration: none;">&larr; Back to Disciplines</a>
</div>

<div class="admin-card" style="max-width: 750px;">
    <h3 style="font: 700 18px 'Playfair Display', serif; margin: 0 0 16px; color: var(--navy);">Discipline Details</h3>

    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Discipline / Category Name <span style="color:#c53030;">*</span>
                </label>
                <input type="text" id="name" name="name" class="field" value="{{ old('name') }}" placeholder="e.g. Geopolitics & Global Strategy" required style="width: 100%;">
            </div>

            <!-- Balanced 3-Column Hierarchy & Icon Row -->
            <div style="display: grid; grid-template-columns: 1.5fr 1.5fr 0.8fr; gap: 16px; align-items: start;">
                <div>
                    <label for="parent_id" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Parent Discipline (Optional)
                    </label>
                    <select id="parent_id" name="parent_id" class="field" style="width: 100%;">
                        <option value="">None (Top-Level Discipline)</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-icon-picker 
                        name="icon" 
                        :value="old('icon', 'strategy')" 
                        label="System Icon"
                    />
                </div>

                <div>
                    <label for="sort_order" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                        Sort Order
                    </label>
                    <input type="number" id="sort_order" name="sort_order" class="field" value="{{ old('sort_order', 0) }}" min="0" style="width: 100%;">
                </div>
            </div>

            <div>
                <label for="description" style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
                    Curriculum Description
                </label>
                <textarea id="description" name="description" rows="3" class="field" placeholder="Brief summary of curriculum focus and executive learning outcomes..." style="width: 100%;">{{ old('description') }}</textarea>
            </div>

            <div style="margin-top: 5px;">
                <button type="submit" class="gold" style="padding: 10px 24px; font-size: 14px;">
                    Save Discipline &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
