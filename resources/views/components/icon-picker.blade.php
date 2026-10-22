@props([
    'name' => 'icon',
    'value' => 'strategy',
    'label' => 'System Icon',
    'help' => null,
])

@php
$availableIcons = [
    ['key' => 'strategy', 'label' => 'Strategy & Leadership'],
    ['key' => 'chart', 'label' => 'Economics & Analytics'],
    ['key' => 'globe', 'label' => 'Geopolitics & Global'],
    ['key' => 'scale', 'label' => 'Governance & Ethics'],
    ['key' => 'academic', 'label' => 'Academia & Research'],
    ['key' => 'book', 'label' => 'Monographs & Books'],
    ['key' => 'insights', 'label' => 'Executive Insights'],
    ['key' => 'collections', 'label' => 'Curated Collections'],
    ['key' => 'shield', 'label' => 'Defense & Security'],
    ['key' => 'sparkles', 'label' => 'Mindset & Innovation'],
    ['key' => 'cog', 'label' => 'Technology & Systems'],
    ['key' => 'microscope', 'label' => 'Scientific Inquiry'],
    ['key' => 'explore', 'label' => 'Strategic Horizons'],
    ['key' => 'authors', 'label' => 'Thought Leadership'],
    ['key' => 'crown', 'label' => 'High Executive'],
    ['key' => 'document', 'label' => 'Policy & Briefings'],
    ['key' => 'database', 'label' => 'Data & Archives'],
    ['key' => 'readers', 'label' => 'Readers & People'],
];

$currentValue = old($name, $value ?: 'strategy');
@endphp

<div 
    x-data="{
        open: false,
        selected: '{{ $currentValue }}',
        search: '',
        icons: {{ json_encode($availableIcons) }},
        selectIcon(key) {
            this.selected = key;
            this.open = false;
        },
        matchesSearch(key, label) {
            if (!this.search.trim()) return true;
            const q = this.search.toLowerCase();
            return key.toLowerCase().includes(q) || label.toLowerCase().includes(q);
        }
    }"
    style="position: relative;"
    @click.outside="open = false"
>
    @if($label)
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 6px;">
            {{ $label }} <span style="color:#c53030;">*</span>
        </label>
    @endif

    <!-- Hidden field for form submission -->
    <input type="hidden" name="{{ $name }}" :value="selected">

    <!-- Trigger Button (Matches standard form input height & styling) -->
    <div 
        @click="open = !open"
        class="icon-picker-trigger"
        style="height: 42px; border: 1px solid var(--border); border-radius: 6px; background: #ffffff; padding: 0 12px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 8px; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box;"
        :style="open ? 'border-color: var(--gold); box-shadow: 0 0 0 2px rgba(185,130,39,0.18);' : ''"
    >
        <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
            <div style="width: 26px; height: 26px; border-radius: 5px; background: #102946; color: #b98227; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                @foreach($availableIcons as $item)
                    <span x-show="selected === '{{ $item['key'] }}'" style="display: flex; align-items: center; justify-content: center;">
                        <x-icon name="{{ $item['key'] }}" size="15" />
                    </span>
                @endforeach
            </div>
            <span style="font-size: 13px; font-weight: 600; color: var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="icons.find(i => i.key === selected)?.label || selected"></span>
        </div>
        <div style="display: flex; align-items: center; color: #64748b; flex-shrink: 0;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :style="open ? 'transform: rotate(180deg);' : ''" style="transition: transform 0.2s;">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </div>

    <!-- Dropdown Floating Menu Panel -->
    <div 
        x-show="open" 
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        style="position: absolute; top: calc(100% + 4px); left: 0; z-index: 100; width: 340px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 12px 28px -4px rgba(16,41,70,0.18), 0 8px 10px -6px rgba(16,41,70,0.1); padding: 10px; display: flex; flex-direction: column; gap: 8px;"
    >
        <!-- Filter Search Bar -->
        <div style="position: relative;">
            <input 
                type="text" 
                x-model="search" 
                placeholder="Search icon (e.g. tech, chart, book)..." 
                style="width: 100%; font-size: 12px; padding: 6px 10px 6px 28px; border: 1px solid #cbd5e1; border-radius: 5px; outline: none; box-sizing: border-box;"
                @click.stop
            >
            <span style="position: absolute; left: 8px; top: 7px; color: #94a3b8; display: flex; align-items: center;">
                <x-icon name="search" size="13" />
            </span>
        </div>

        <!-- Scrollable Grid of Icons -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; max-height: 240px; overflow-y: auto; padding-right: 2px;">
            @foreach($availableIcons as $item)
                <button 
                    type="button"
                    x-show="matchesSearch('{{ $item['key'] }}', '{{ $item['label'] }}')"
                    @click="selectIcon('{{ $item['key'] }}')"
                    class="icon-picker-btn"
                    :class="{ 'is-active': selected === '{{ $item['key'] }}' }"
                >
                    <span class="icon-wrap">
                        <x-icon name="{{ $item['key'] }}" size="16" />
                    </span>
                    <span class="icon-label">{{ $item['label'] }}</span>
                    <template x-if="selected === '{{ $item['key'] }}'">
                        <span class="check-mark">
                            <x-icon name="check" size="12" />
                        </span>
                    </template>
                </button>
            @endforeach
        </div>

        <!-- Custom key input option -->
        <div style="border-top: 1px solid #f1f5f9; padding-top: 6px; display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #64748b;">
            <span>Or custom icon key:</span>
            <input 
                type="text" 
                x-model="selected" 
                placeholder="icon_key" 
                style="width: 120px; font-size: 11px; padding: 3px 6px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;"
                @click.stop
            >
        </div>
    </div>

    @if($help)
        <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 4px;">{{ $help }}</small>
    @endif
</div>

<style>
.icon-picker-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s ease;
    width: 100%;
    position: relative;
    box-sizing: border-box;
}
.icon-picker-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.icon-picker-btn.is-active {
    background: #fffdf5 !important;
    border-color: #b98227 !important;
    box-shadow: 0 0 0 1px #b98227 !important;
}
.icon-picker-btn .icon-wrap {
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.icon-picker-btn.is-active .icon-wrap {
    color: #b98227;
}
.icon-picker-btn .icon-label {
    font-size: 11.5px;
    line-height: 1.2;
    color: #334155;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.icon-picker-btn.is-active .icon-label {
    font-weight: 700;
    color: #102946;
}
.icon-picker-btn .check-mark {
    margin-left: auto;
    color: #b98227;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}
</style>
