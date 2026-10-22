@props([
    'name',
    'items' => [],
    'selected' => [],
    'placeholder' => 'Select options...',
    'label' => null,
    'help' => null,
])

@php
// Normalize items to array of ['id' => ..., 'name' => ...]
$normalizedItems = collect($items)->map(function($item) {
    if (is_array($item)) {
        return [
            'id' => (int) $item['id'],
            'name' => (string) ($item['name'] ?? ($item['title'] ?? '')),
        ];
    }
    return [
        'id' => (int) $item->id,
        'name' => (string) ($item->name ?? ($item->title ?? '')),
    ];
})->values()->all();

$initialSelected = array_values(array_map('intval', (array) $selected));
@endphp

<div 
    x-data="{
        open: false,
        search: '',
        items: {{ json_encode($normalizedItems) }},
        selected: {{ json_encode($initialSelected) }},
        toggle(id) {
            id = parseInt(id);
            const idx = this.selected.indexOf(id);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push(id);
            }
        },
        isSelected(id) {
            return this.selected.includes(parseInt(id));
        },
        removeItem(id) {
            id = parseInt(id);
            const idx = this.selected.indexOf(id);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            }
        },
        getSelectedItems() {
            return this.items.filter(item => this.isSelected(item.id));
        },
        get filteredItems() {
            if (!this.search.trim()) return this.items;
            const q = this.search.toLowerCase();
            return this.items.filter(item => item.name.toLowerCase().includes(q));
        }
    }"
    style="position: relative;"
    @click.outside="open = false"
>
    @if($label)
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy);">
            {{ $label }}
        </label>
    @endif

    <!-- Trigger Box -->
    <div 
        @click="open = !open"
        style="min-height: 42px; border: 1px solid var(--border); border-radius: 6px; background: #ffffff; padding: 6px 10px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 8px; transition: border-color 0.2s, box-shadow 0.2s;"
        :style="open ? 'border-color: var(--gold); box-shadow: 0 0 0 2px rgba(185,130,39,0.18);' : ''"
    >
        <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1;">
            <template x-if="selected.length === 0">
                <span style="color: var(--muted); font-size: 13px;">{{ $placeholder }}</span>
            </template>
            <template x-for="item in getSelectedItems()" :key="item.id">
                <span 
                    style="display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; color: var(--navy); border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 8px; font-size: 12px; font-weight: 500;"
                    @click.stop
                >
                    <span x-text="item.name"></span>
                    <button 
                        type="button" 
                        @click.stop="removeItem(item.id)"
                        style="background: none; border: none; padding: 0 2px; cursor: pointer; font-size: 14px; line-height: 1; color: #64748b;"
                        title="Remove"
                    >&times;</button>
                </span>
            </template>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; color: #64748b; flex-shrink: 0;">
            <template x-if="selected.length > 0">
                <span style="font-size: 11px; background: #e2e8f0; color: #334155; border-radius: 10px; padding: 1px 7px; font-weight: 600;" x-text="selected.length"></span>
            </template>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :style="open ? 'transform: rotate(180deg);' : ''" style="transition: transform 0.2s;">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </div>

    <!-- Hidden Form Inputs for Submission -->
    <template x-for="id in selected" :key="id">
        <input type="hidden" name="{{ $name }}" :value="id">
    </template>

    <!-- Floating Dropdown Panel -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 100; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(16,41,70,0.18), 0 8px 10px -6px rgba(16,41,70,0.1); max-height: 280px; display: flex; flex-direction: column; overflow: hidden;"
    >
        <!-- Search filter input -->
        <div style="padding: 8px; border-bottom: 1px solid #e2e8f0; background: #fafaf9;">
            <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 8px; color: #94a3b8; display: flex; align-items: center;">
                    <x-icon name="search" size="13" />
                </span>
                <input 
                    type="text" 
                    x-model="search" 
                    placeholder="Type to filter..." 
                    style="width: 100%; padding: 6px 10px 6px 28px; font-size: 12.5px; border: 1px solid #cbd5e1; border-radius: 5px; outline: none;"
                    @click.stop
                >
            </div>
        </div>

        <!-- Scrollable options list -->
        <div style="padding: 4px; overflow-y: auto; flex: 1; max-height: 200px;">
            <template x-for="item in filteredItems" :key="item.id">
                <label 
                    style="display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; border-radius: 5px; font-size: 13px; cursor: pointer; transition: background 0.15s;"
                    :style="isSelected(item.id) ? 'background: #fffdf5; font-weight: 600; color: var(--navy);' : 'color: #334155;'"
                    @click.stop="toggle(item.id)"
                >
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            :checked="isSelected(item.id)" 
                            style="width: 15px; height: 15px; accent-color: var(--gold); cursor: pointer;"
                            @click.stop="toggle(item.id)"
                        >
                        <span x-text="item.name"></span>
                    </span>
                    <template x-if="isSelected(item.id)">
                        <span style="color: var(--gold); font-size: 12px; display: flex; align-items: center;">
                            <x-icon name="check" size="13" />
                        </span>
                    </template>
                </label>
            </template>
            <template x-if="filteredItems.length === 0">
                <div style="padding: 14px; text-align: center; color: var(--muted); font-size: 12.5px;">
                    No matches found
                </div>
            </template>
        </div>

        <!-- Footer helper / Clear selection -->
        <div style="padding: 6px 10px; border-top: 1px solid #f1f5f9; background: #fafaf9; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #64748b;">
            <span x-text="`${selected.length} selected`"></span>
            <template x-if="selected.length > 0">
                <button 
                    type="button" 
                    @click.stop="selected = []" 
                    style="background: none; border: none; color: #b91c1c; cursor: pointer; font-size: 11px; padding: 0;"
                >
                    Clear all
                </button>
            </template>
        </div>
    </div>

    @if($help)
        <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 4px;">{{ $help }}</small>
    @endif
</div>
