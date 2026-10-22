import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.data('publicNavigation', () => ({
    mobileNav: false,
    breakpoint: null,
    onResize: null,
    init() {
        this.breakpoint = window.matchMedia('(min-width: 1101px)');
        this.onResize = () => { if (this.breakpoint.matches) this.close(); };
        this.breakpoint.addEventListener('change', this.onResize);
        this.$el.querySelectorAll('#nav a, .bottom a').forEach(link => {
            if (link.classList.contains('active')) link.setAttribute('aria-current', 'page');
        });
    },
    toggle() {
        this.mobileNav = !this.mobileNav;
        if (this.mobileNav) this.$nextTick(() => this.$refs.primaryNav.querySelector('a')?.focus());
    },
    close(returnFocus = false) {
        if (!this.mobileNav) return;
        this.mobileNav = false;
        if (returnFocus) this.$refs.menuToggle.focus();
    },
    destroy() { this.breakpoint.removeEventListener('change', this.onResize); }
}));

Alpine.data('heroSlider', total => ({
    current: 0, total, timer: null, hovered: false, focused: false, playing: true,
    init() {
        this.playing = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (this.total > 1) this.timer = window.setInterval(() => {
            if (this.playing && !this.hovered && !this.focused && !document.hidden) this.next();
        }, 6500);
    },
    next() { this.current = (this.current + 1) % this.total; },
    prev() { this.current = (this.current - 1 + this.total) % this.total; },
    goTo(index) { this.current = index; },
    destroy() { window.clearInterval(this.timer); }
}));

Alpine.data('catalogueFaceted', (config) => ({
    search: config.search || '',
    searchBy: config.searchBy || 'all',
    libraries: config.libraries || [],
    categories: config.categories || [],
    types: config.types || [],
    years: config.years || [],
    sort: config.sort || 'latest',
    viewMode: config.viewMode || 'grid',
    facetCounts: config.facetCounts || {},
    resultsCount: config.resultsCount || 0,
    endpoint: config.endpoint || window.location.pathname,
    isLibraryScoped: Boolean(config.isLibraryScoped),
    mobileFiltersOpen: false,
    collapsedSections: {
        libraries: false,
        categories: false,
        types: false,
        years: false
    },
    timeout: null,
    loading: false,
    activeRequestId: 0,

    init() {
        this.searchBy = this.normalizeSearchBy(this.searchBy);
        window.addEventListener('popstate', () => {
            const params = new URLSearchParams(window.location.search);
            this.search = params.get('q') || '';
            this.searchBy = this.normalizeSearchBy(params.get('search_by'));
            if (!this.isLibraryScoped) {
                this.libraries = params.get('library') ? params.get('library').split(',').map(s => s.trim()).filter(Boolean) : [];
            }
            this.categories = params.get('category') ? params.get('category').split(',').map(s => s.trim()).filter(Boolean) : [];
            this.types = params.get('type') ? params.get('type').split(',').map(s => s.trim()).filter(Boolean) : [];
            this.years = params.get('year') ? params.get('year').split(',').map(s => s.trim()).filter(Boolean) : [];
            this.sort = params.get('sort') || 'latest';
            this.viewMode = params.get('view') || 'grid';
            this.fetchBooks(false);
        });
    },

    normalizeSearchBy(val) {
        if (!val) return 'all';
        const clean = String(val).toLowerCase().trim();
        const aliases = {
            'authors': 'author',
            'creator': 'author',
            'creators': 'author',
            'discipline': 'subject',
            'disciplines': 'subject',
            'category': 'subject',
            'categories': 'subject',
            'tag': 'tags',
            'keywords': 'tags',
            'keyword': 'tags',
            'isbn': 'isbn_doi',
            'doi': 'isbn_doi',
            'identifier': 'isbn_doi',
            'identifiers': 'isbn_doi',
            'publisher': 'publisher',
            'publishers': 'publisher',
            'series': 'series',
        };
        const resolved = aliases[clean] || clean;
        const valid = ['all', 'title', 'author', 'subject', 'tags', 'isbn_doi', 'publisher', 'series'];
        return valid.includes(resolved) ? resolved : 'all';
    },

    activeFilterCount() {
        let count = 0;
        if (this.search) count++;
        if (!this.isLibraryScoped) {
            count += this.libraries.length;
        }
        count += this.categories.length;
        count += this.types.length;
        count += this.years.length;
        return count;
    },

    updateSearch() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.fetchBooks(false);
        }, 300);
    },

    submitSearch() {
        clearTimeout(this.timeout);
        this.fetchBooks(true);
    },

    changeSearchBy() {
        this.fetchBooks(true);
    },

    clearSearchInput() {
        this.search = '';
        this.fetchBooks(true);
    },

    getSearchPlaceholder() {
        switch (this.searchBy) {
            case 'title': return 'Search by title, subtitle...';
            case 'author': return 'Search by author, creator...';
            case 'subject': return 'Search by discipline, subject...';
            case 'tags': return 'Search by tag or keyword...';
            case 'isbn_doi': return 'Search by ISBN or DOI...';
            case 'publisher': return 'Search by publisher...';
            case 'series': return 'Search by series name...';
            default: return 'Search across all fields...';
        }
    },

    getSearchByLabel() {
        const labels = {
            'all': 'All Fields',
            'title': 'Title',
            'author': 'Author',
            'subject': 'Discipline',
            'tags': 'Tag',
            'isbn_doi': 'ISBN / DOI',
            'publisher': 'Publisher',
            'series': 'Series'
        };
        return labels[this.searchBy] || 'Search';
    },

    toggleLibrary(slug) {
        const index = this.libraries.indexOf(slug);
        if (index > -1) {
            this.libraries.splice(index, 1);
        } else {
            this.libraries.push(slug);
        }
        this.fetchBooks(true);
    },

    clearLibraries() {
        this.libraries = [];
        this.fetchBooks(true);
    },

    toggleCategory(slug) {
        const index = this.categories.indexOf(slug);
        if (index > -1) {
            this.categories.splice(index, 1);
        } else {
            this.categories.push(slug);
        }
        this.fetchBooks(true);
    },

    clearCategories() {
        this.categories = [];
        this.fetchBooks(true);
    },

    toggleType(typeKey) {
        const index = this.types.indexOf(typeKey);
        if (index > -1) {
            this.types.splice(index, 1);
        } else {
            this.types.push(typeKey);
        }
        this.fetchBooks(true);
    },

    clearTypes() {
        this.types = [];
        this.fetchBooks(true);
    },

    toggleYear(yearRange) {
        const index = this.years.indexOf(yearRange);
        if (index > -1) {
            this.years.splice(index, 1);
        } else {
            this.years.push(yearRange);
        }
        this.fetchBooks(true);
    },

    clearYears() {
        this.years = [];
        this.fetchBooks(true);
    },

    setViewMode(mode) {
        if (this.viewMode === mode) return;
        this.viewMode = mode;
        this.fetchBooks(true);
    },

    changeSort() {
        this.fetchBooks(true);
    },

    buildQueryParams() {
        const params = new URLSearchParams();
        if (this.search) params.set('q', this.search);
        if (this.searchBy && this.searchBy !== 'all') {
            params.set('search_by', this.searchBy);
        }
        if (!this.isLibraryScoped && this.libraries.length) {
            params.set('library', this.libraries.join(','));
        }
        if (this.categories.length) params.set('category', this.categories.join(','));
        if (this.types.length) params.set('type', this.types.join(','));
        if (this.years.length) params.set('year', this.years.join(','));
        if (this.sort && this.sort !== 'latest') params.set('sort', this.sort);
        if (this.viewMode && this.viewMode !== 'grid') params.set('view', this.viewMode);
        return params;
    },

    fetchBooks(pushHistory = true) {
        const requestId = ++this.activeRequestId;
        this.loading = true;

        const params = this.buildQueryParams();
        const queryStr = params.toString();
        const newUrl = `${window.location.pathname}${queryStr ? '?' + queryStr : ''}`;

        if (pushHistory) {
            window.history.pushState({}, '', newUrl);
        } else {
            window.history.replaceState({}, '', newUrl);
        }

        fetch(`${this.endpoint}?${queryStr}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (requestId !== this.activeRequestId) return;

            const resultsContainer = document.getElementById('book-results') || document.getElementById('library-books-results');
            if (resultsContainer && data.html) {
                resultsContainer.innerHTML = data.html;
            }
            const counter = document.getElementById('results-counter');
            if (counter && data.count !== undefined) {
                const label = this.isLibraryScoped 
                    ? `${data.count} ${data.count === 1 ? 'Title' : 'Titles'} Available`
                    : `${data.count} ${data.count === 1 ? 'title' : 'titles'} available`;
                counter.innerText = label;
            }
            const statsCounter = document.getElementById('stats-counter');
            if (statsCounter && data.count !== undefined) {
                statsCounter.innerText = `${data.count}`;
            }
            const paginationContainer = document.getElementById('pagination-container');
            if (paginationContainer) {
                paginationContainer.innerHTML = data.pagination || '';
            }
            if (data.facet_counts) {
                this.facetCounts = data.facet_counts;
            }
            this.resultsCount = data.count || 0;
            this.loading = false;
        })
        .catch(() => {
            if (requestId === this.activeRequestId) {
                this.loading = false;
            }
        });
    },

    clearSearch() {
        this.search = '';
        this.searchBy = 'all';
        if (!this.isLibraryScoped) {
            this.libraries = [];
        }
        this.categories = [];
        this.types = [];
        this.years = [];
        this.fetchBooks(true);
    }
}));

Alpine.start();
