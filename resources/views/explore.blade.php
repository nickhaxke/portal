@extends('layouts.app')

@section('title', 'Explore Digital Library Catalogue')

@section('content')
@php
    $catalogueConfig = [
        'search' => $query,
        'searchBy' => $searchBy ?? ($filters['search_by'] ?? 'all'),
        'libraries' => $filters['libraries'],
        'categories' => $filters['categories'],
        'types' => $filters['types'],
        'years' => $filters['years'],
        'sort' => $sort,
        'viewMode' => $view,
        'facetCounts' => $facetCounts,
        'resultsCount' => $books->total(),
        'endpoint' => route('explore'),
        'isLibraryScoped' => false,
    ];
@endphp

<section class="catalogue-wrap" 
    x-data="catalogueFaceted(@js($catalogueConfig))"
    @reset-filters.window="clearSearch()"
>
    <!-- Catalogue Header Section -->
    <div style="margin-bottom: 28px;">
        <div class="eyebrow" style="margin-bottom: 4px;">Universal Knowledge Catalogue</div>
        <h1 style="margin: 0 0 8px; font-size: 32px; color: var(--navy); font-family: 'DM Sans', sans-serif; font-weight: 800;">
            Explore Publications
        </h1>
        <p style="color: var(--muted); font-size: 15px; margin: 0; max-width: 800px; line-height: 1.6;">
            Search and filter the curated executive collection across digital libraries, disciplines, publication types, and historical eras.
        </p>
    </div>

    <!-- Main Faceted Layout -->
    <div class="catalogue-layout">
        <!-- Left Sidebar: Faceted Filters -->
        <aside class="catalogue-sidebar" :class="{ 'drawer-open': mobileFiltersOpen }" aria-label="Catalogue Filters" @keydown.escape.window="mobileFiltersOpen = false">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <x-icon name="filter" size="16" />
                    <span>Catalog Filters</span>
                    <span class="facet-count" style="margin-left: 4px;" x-show="activeFilterCount() > 0" x-text="activeFilterCount()"></span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="sidebar-reset-btn" x-show="activeFilterCount() > 0" @click="clearSearch()">
                        Reset All
                    </button>
                    <button type="button" class="sidebar-drawer-close" style="display: none; background: none; border: none; cursor: pointer; color: var(--navy);" @click="mobileFiltersOpen = false" aria-label="Close filters drawer">
                        <x-icon name="close" size="18" />
                    </button>
                </div>
            </div>

            <!-- Fast Search Facet (Combined Search Control) -->
            <div class="sidebar-search-box">
                <div class="catalogue-search-bar" role="search" aria-label="Catalogue Search">
                    <label for="catalogue-search-field" class="sr-only">Search Field</label>
                    <select 
                        id="catalogue-search-field"
                        class="catalogue-search-field-select" 
                        x-model="searchBy" 
                        @change="changeSearchBy()"
                        aria-label="Select search field"
                    >
                        <option value="all">All Fields</option>
                        <option value="title">Title</option>
                        <option value="author">Author / Creator</option>
                        <option value="subject">Subject / Discipline</option>
                        <option value="tags">Tags / Keywords</option>
                        <option value="isbn_doi">ISBN / DOI</option>
                        <option value="publisher">Publisher</option>
                        <option value="series">Series</option>
                    </select>

                    <div class="catalogue-search-input-wrap">
                        <span class="search-icon">
                            <x-icon name="search" size="14" />
                        </span>
                        <input 
                            id="catalogue-search-input"
                            type="text" 
                            class="sidebar-search-input catalogue-search-input" 
                            :placeholder="getSearchPlaceholder()" 
                            x-model="search" 
                            @input="updateSearch()"
                            @keydown.enter.prevent="submitSearch()"
                            aria-label="Enter search terms"
                        >
                        <button 
                            type="button" 
                            class="sidebar-search-clear catalogue-search-clear" 
                            x-show="search.length > 0" 
                            @click="clearSearchInput()" 
                            aria-label="Clear search input"
                        >
                            &times;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Facet: Digital Libraries -->
            @if($libraries->isNotEmpty())
                <div class="facet-section" style="border-top: none; padding-top: 0; margin-top: 0;">
                    <button type="button" class="facet-header-btn" @click="collapsedSections.libraries = !collapsedSections.libraries" :aria-expanded="!collapsedSections.libraries">
                        <div class="facet-header" style="margin-bottom: 0;">
                            <span>Digital Libraries</span>
                            <span class="facet-chevron" :style="collapsedSections.libraries ? 'transform: rotate(-90deg)' : ''">
                                <x-icon name="chevron-down" size="13" />
                            </span>
                        </div>
                    </button>

                    <div class="facet-list" x-show="!collapsedSections.libraries" x-transition>
                        <button 
                            type="button" 
                            class="facet-item" 
                            :class="{ 'active': libraries.length === 0 }" 
                            @click="clearLibraries()"
                            role="button"
                            aria-label="All Libraries"
                        >
                            <span class="facet-label-wrap">
                                <x-icon name="library" size="13" />
                                <span>All Libraries</span>
                            </span>
                            <span class="facet-count" x-text="facetCounts.libraries_total !== undefined ? facetCounts.libraries_total : '{{ $facetCounts['libraries_total'] }}'">{{ $facetCounts['libraries_total'] }}</span>
                        </button>

                        @foreach($libraries as $lib)
                            <button 
                                type="button" 
                                class="facet-item" 
                                :class="{ 'active': libraries.includes('{{ $lib->slug }}') }" 
                                @click="toggleLibrary('{{ $lib->slug }}')"
                                role="checkbox"
                                :aria-checked="libraries.includes('{{ $lib->slug }}')"
                            >
                                <span class="facet-label-wrap">
                                    <span class="facet-checkbox" :class="{ 'checked': libraries.includes('{{ $lib->slug }}') }">
                                        <svg x-show="libraries.includes('{{ $lib->slug }}')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                    <x-icon :name="$lib->icon_name ?? 'library'" size="13" />
                                    <span>{{ $lib->name }}</span>
                                </span>
                                <span class="facet-count" x-text="facetCounts.libraries && facetCounts.libraries[{{ $lib->id }}] !== undefined ? facetCounts.libraries[{{ $lib->id }}] : 0">
                                    {{ $facetCounts['libraries'][$lib->id] ?? 0 }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Facet: Disciplines & Subjects -->
            @if($categories->isNotEmpty())
                <div class="facet-section">
                    <button type="button" class="facet-header-btn" @click="collapsedSections.categories = !collapsedSections.categories" :aria-expanded="!collapsedSections.categories">
                        <div class="facet-header" style="margin-bottom: 0;">
                            <span>Disciplines</span>
                            <span class="facet-chevron" :style="collapsedSections.categories ? 'transform: rotate(-90deg)' : ''">
                                <x-icon name="chevron-down" size="13" />
                            </span>
                        </div>
                    </button>

                    <div class="facet-list" x-show="!collapsedSections.categories" x-transition>
                        <button 
                            type="button" 
                            class="facet-item" 
                            :class="{ 'active': categories.length === 0 }" 
                            @click="clearCategories()"
                            role="button"
                            aria-label="All Disciplines"
                        >
                            <span class="facet-label-wrap">
                                <x-icon name="folder" size="13" />
                                <span>All Disciplines</span>
                            </span>
                            <span class="facet-count" x-text="facetCounts.categories_total !== undefined ? facetCounts.categories_total : '{{ $facetCounts['categories_total'] }}'">{{ $facetCounts['categories_total'] }}</span>
                        </button>

                        @foreach($categories as $cat)
                            <button 
                                type="button" 
                                class="facet-item" 
                                :class="{ 'active': categories.includes('{{ $cat->slug }}') }" 
                                @click="toggleCategory('{{ $cat->slug }}')"
                                role="checkbox"
                                :aria-checked="categories.includes('{{ $cat->slug }}')"
                            >
                                <span class="facet-label-wrap">
                                    <span class="facet-checkbox" :class="{ 'checked': categories.includes('{{ $cat->slug }}') }">
                                        <svg x-show="categories.includes('{{ $cat->slug }}')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                    <x-icon :name="$cat->icon_name ?? 'folder'" size="13" />
                                    <span>{{ $cat->name }}</span>
                                </span>
                                <span class="facet-count" x-text="facetCounts.categories && facetCounts.categories[{{ $cat->id }}] !== undefined ? facetCounts.categories[{{ $cat->id }}] : 0">
                                    {{ $facetCounts['categories'][$cat->id] ?? 0 }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Facet: Publication Types -->
            <div class="facet-section">
                <button type="button" class="facet-header-btn" @click="collapsedSections.types = !collapsedSections.types" :aria-expanded="!collapsedSections.types">
                    <div class="facet-header" style="margin-bottom: 0;">
                        <span>Publication Types</span>
                        <span class="facet-chevron" :style="collapsedSections.types ? 'transform: rotate(-90deg)' : ''">
                            <x-icon name="chevron-down" size="13" />
                        </span>
                    </div>
                </button>

                <div class="facet-list" x-show="!collapsedSections.types" x-transition>
                    <button 
                        type="button" 
                        class="facet-item" 
                        :class="{ 'active': types.length === 0 }" 
                        @click="clearTypes()"
                        role="button"
                        aria-label="All Types"
                    >
                        <span class="facet-label-wrap">
                            <x-icon name="book" size="13" />
                            <span>All Types</span>
                        </span>
                        <span class="facet-count" x-text="facetCounts.types_total !== undefined ? facetCounts.types_total : '{{ $facetCounts['types_total'] }}'">{{ $facetCounts['types_total'] }}</span>
                    </button>

                    @foreach($publicationTypes as $typeKey => $typeData)
                        <button 
                            type="button" 
                            class="facet-item" 
                            :class="{ 'active': types.includes('{{ $typeKey }}') }" 
                            @click="toggleType('{{ $typeKey }}')"
                            role="checkbox"
                            :aria-checked="types.includes('{{ $typeKey }}')"
                        >
                            <span class="facet-label-wrap">
                                <span class="facet-checkbox" :class="{ 'checked': types.includes('{{ $typeKey }}') }">
                                    <svg x-show="types.includes('{{ $typeKey }}')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </span>
                                @if($typeKey === 'research_paper')
                                    <x-icon name="file-text" size="13" />
                                @elseif($typeKey === 'report')
                                    <x-icon name="chart" size="13" />
                                @elseif($typeKey === 'policy_document')
                                    <x-icon name="shield" size="13" />
                                @elseif($typeKey === 'thesis')
                                    <x-icon name="thesis" size="13" />
                                @else
                                    <x-icon name="book" size="13" />
                                @endif
                                <span>{{ $typeData['name'] }}</span>
                            </span>
                            <span class="facet-count" x-text="facetCounts.types && facetCounts.types['{{ $typeKey }}'] !== undefined ? facetCounts.types['{{ $typeKey }}'] : 0">
                                {{ $facetCounts['types'][$typeKey] ?? 0 }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Facet: Publication Era / Year -->
            <div class="facet-section">
                <button type="button" class="facet-header-btn" @click="collapsedSections.years = !collapsedSections.years" :aria-expanded="!collapsedSections.years">
                    <div class="facet-header" style="margin-bottom: 0;">
                        <span>Publication Era</span>
                        <span class="facet-chevron" :style="collapsedSections.years ? 'transform: rotate(-90deg)' : ''">
                            <x-icon name="chevron-down" size="13" />
                        </span>
                    </div>
                </button>

                <div class="facet-list" x-show="!collapsedSections.years" x-transition>
                    <button 
                        type="button" 
                        class="facet-item" 
                        :class="{ 'active': years.length === 0 }" 
                        @click="clearYears()"
                        role="button"
                        aria-label="All Eras"
                    >
                        <span class="facet-label-wrap">
                            <x-icon name="calendar" size="13" />
                            <span>All Eras</span>
                        </span>
                        <span class="facet-count" x-text="facetCounts.years_total !== undefined ? facetCounts.years_total : '{{ $facetCounts['years_total'] }}'">{{ $facetCounts['years_total'] }}</span>
                    </button>

                    <button 
                        type="button" 
                        class="facet-item" 
                        :class="{ 'active': years.includes('2025-2026') }" 
                        @click="toggleYear('2025-2026')"
                        role="checkbox"
                        :aria-checked="years.includes('2025-2026')"
                    >
                        <span class="facet-label-wrap">
                            <span class="facet-checkbox" :class="{ 'checked': years.includes('2025-2026') }">
                                <svg x-show="years.includes('2025-2026')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span>Recent (2025–2026)</span>
                        </span>
                        <span class="facet-count" x-text="facetCounts.years && facetCounts.years['2025-2026'] !== undefined ? facetCounts.years['2025-2026'] : 0">
                            {{ $facetCounts['years']['2025-2026'] ?? 0 }}
                        </span>
                    </button>

                    <button 
                        type="button" 
                        class="facet-item" 
                        :class="{ 'active': years.includes('2020-2024') }" 
                        @click="toggleYear('2020-2024')"
                        role="checkbox"
                        :aria-checked="years.includes('2020-2024')"
                    >
                        <span class="facet-label-wrap">
                            <span class="facet-checkbox" :class="{ 'checked': years.includes('2020-2024') }">
                                <svg x-show="years.includes('2020-2024')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span>2020–2024</span>
                        </span>
                        <span class="facet-count" x-text="facetCounts.years && facetCounts.years['2020-2024'] !== undefined ? facetCounts.years['2020-2024'] : 0">
                            {{ $facetCounts['years']['2020-2024'] ?? 0 }}
                        </span>
                    </button>

                    <button 
                        type="button" 
                        class="facet-item" 
                        :class="{ 'active': years.includes('2015-2019') }" 
                        @click="toggleYear('2015-2019')"
                        role="checkbox"
                        :aria-checked="years.includes('2015-2019')"
                    >
                        <span class="facet-label-wrap">
                            <span class="facet-checkbox" :class="{ 'checked': years.includes('2015-2019') }">
                                <svg x-show="years.includes('2015-2019')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span>2015–2019</span>
                        </span>
                        <span class="facet-count" x-text="facetCounts.years && facetCounts.years['2015-2019'] !== undefined ? facetCounts.years['2015-2019'] : 0">
                            {{ $facetCounts['years']['2015-2019'] ?? 0 }}
                        </span>
                    </button>

                    <button 
                        type="button" 
                        class="facet-item" 
                        :class="{ 'active': years.includes('older') }" 
                        @click="toggleYear('older')"
                        role="checkbox"
                        :aria-checked="years.includes('older')"
                    >
                        <span class="facet-label-wrap">
                            <span class="facet-checkbox" :class="{ 'checked': years.includes('older') }">
                                <svg x-show="years.includes('older')" viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span>Archive (Before 2015)</span>
                        </span>
                        <span class="facet-count" x-text="facetCounts.years && facetCounts.years['older'] !== undefined ? facetCounts.years['older'] : 0">
                            {{ $facetCounts['years']['older'] ?? 0 }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer Bottom Action -->
            <div class="mobile-drawer-footer" style="margin-top: 24px; padding-top: 14px; border-top: 1px solid #edf2f7;">
                <button type="button" class="action-btn primary" style="width: 100%; justify-content: center; padding: 10px;" @click="mobileFiltersOpen = false">
                    Show <span x-text="resultsCount"></span> Results
                </button>
            </div>
        </aside>

        <!-- Backdrop for mobile drawer -->
        <div class="drawer-backdrop" x-show="mobileFiltersOpen" @click="mobileFiltersOpen = false" style="display: none;" x-transition.opacity></div>

        <!-- Right Side: Catalogue Main Area -->
        <main class="catalogue-main">
            <!-- Top Controls Bar -->
            <div class="catalogue-header-bar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <!-- Mobile Filter Trigger -->
                    <button type="button" class="mobile-filter-trigger" @click="mobileFiltersOpen = true">
                        <x-icon name="filter" size="14" />
                        <span>Filters</span>
                        <span class="facet-count" x-show="activeFilterCount() > 0" x-text="activeFilterCount()"></span>
                    </button>

                    <div class="catalogue-stats">
                        Showing <strong id="stats-counter" x-text="resultsCount">{{ $books->total() }}</strong> curated publications
                    </div>
                </div>

                <div class="catalogue-controls">
                    <!-- Sort Selector -->
                    <div class="sort-wrapper">
                        <label for="sort-select">Sort by:</label>
                        <select id="sort-select" class="sort-select" x-model="sort" @change="changeSort()">
                            <option value="latest">Newest Additions</option>
                            <option value="title_asc">Title (A &rarr; Z)</option>
                            <option value="title_desc">Title (Z &rarr; A)</option>
                            <option value="year_desc">Year (Newest First)</option>
                            <option value="year_asc">Year (Oldest First)</option>
                            <option value="author_asc">Author (A &rarr; Z)</option>
                        </select>
                    </div>

                    <!-- View Switcher (Grid vs List) -->
                    <div class="view-switchers" role="group" aria-label="Catalog view style">
                        <button 
                            type="button" 
                            class="view-btn" 
                            :class="{ 'active': viewMode === 'grid' }" 
                            @click="setViewMode('grid')" 
                            title="Grid View"
                        >
                            <x-icon name="grid" size="13" />
                            <span>Grid</span>
                        </button>
                        <button 
                            type="button" 
                            class="view-btn" 
                            :class="{ 'active': viewMode === 'list' }" 
                            @click="setViewMode('list')" 
                            title="Bibliographic List View"
                        >
                            <x-icon name="list" size="13" />
                            <span>List</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filters Row (Removable Badges) -->
            <div class="active-filters-bar" x-show="activeFilterCount() > 0" style="display: none;">
                <span class="active-filters-label">Active Filters:</span>

                <template x-if="search">
                    <span class="active-filter-pill">
                        <strong><span x-text="getSearchByLabel()"></span>:</strong> <span x-text="search"></span>
                        <button type="button" class="active-filter-remove" @click="clearSearchInput()" aria-label="Remove search filter">&times;</button>
                    </span>
                </template>

                <template x-for="libSlug in libraries" :key="'lib-' + libSlug">
                    <span class="active-filter-pill">
                        <strong>Library:</strong> <span x-text="libSlug"></span>
                        <button type="button" class="active-filter-remove" @click="toggleLibrary(libSlug)" :aria-label="'Remove ' + libSlug + ' filter'">&times;</button>
                    </span>
                </template>

                <template x-for="catSlug in categories" :key="'cat-' + catSlug">
                    <span class="active-filter-pill">
                        <strong>Discipline:</strong> <span x-text="catSlug"></span>
                        <button type="button" class="active-filter-remove" @click="toggleCategory(catSlug)" :aria-label="'Remove ' + catSlug + ' filter'">&times;</button>
                    </span>
                </template>

                <template x-for="tKey in types" :key="'type-' + tKey">
                    <span class="active-filter-pill">
                        <strong>Type:</strong> <span x-text="tKey"></span>
                        <button type="button" class="active-filter-remove" @click="toggleType(tKey)" :aria-label="'Remove ' + tKey + ' filter'">&times;</button>
                    </span>
                </template>

                <template x-for="yRange in years" :key="'year-' + yRange">
                    <span class="active-filter-pill">
                        <strong>Era:</strong> <span x-text="yRange"></span>
                        <button type="button" class="active-filter-remove" @click="toggleYear(yRange)" :aria-label="'Remove ' + yRange + ' filter'">&times;</button>
                    </span>
                </template>

                <button type="button" class="active-filter-clear-all" @click="clearSearch()">
                    Reset All Filters
                </button>
            </div>

            <!-- Dynamic Book Results Container -->
            <div id="book-results" :style="loading ? 'opacity: 0.45; pointer-events: none; transition: opacity 0.2s;' : 'transition: opacity 0.2s;'">
                @if($view === 'list')
                    <x-books-list :books="$books" />
                @else
                    <x-books-grid :books="$books" />
                @endif
            </div>

            <div id="pagination-container" style="margin-top: 40px;">
                {{ $books->links() }}
            </div>
        </main>
    </div>
</section>
@endsection
