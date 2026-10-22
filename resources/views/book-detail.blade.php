@extends('layouts.app')

@section('title', $book->title . ($book->author_name ? ' — ' . $book->author_name : ''))

@section('content')
<section class="wrap page biblio-page" x-data="{
    saved: {{ $isSaved ? 'true' : 'false' }},
    loading: false,
    citationCopied: false,
    toggleSave() {
        this.loading = true;
        fetch('{{ route('books.toggle-save', $book->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            this.saved = data.saved;
            this.loading = false;
        })
        .catch(() => {
            this.loading = false;
        });
    },
    copyCitation() {
        const text = document.getElementById('citation-text').innerText.trim();
        navigator.clipboard.writeText(text).then(() => {
            this.citationCopied = true;
            setTimeout(() => this.citationCopied = false, 2500);
        });
    }
}">
    {{-- Minimal Breadcrumb Navigation --}}
    <nav class="biblio-breadcrumbs" aria-label="Breadcrumb Navigation">
        <a href="{{ route('explore') }}" class="biblio-crumb">&larr; Repository Catalogue</a>
        @if($book->library)
            <span class="biblio-crumb-sep">/</span>
            <a href="{{ route('libraries.show', $book->library->slug) }}" class="biblio-crumb">{{ $book->library->name }}</a>
        @endif
        @if($book->category)
            <span class="biblio-crumb-sep">/</span>
            <a href="{{ route('explore', ['category' => $book->category->slug]) }}" class="biblio-crumb">{{ $book->category->name }}</a>
        @endif
        <span class="biblio-crumb-sep">/</span>
        <span class="biblio-crumb current" aria-current="page">{{ Str::limit($book->title, 45) }}</span>
    </nav>

    <div class="details biblio-layout">
        {{-- ============================================================
             LEFT COLUMN: Book Cover, Single Reading Action & Single Save
             ============================================================ --}}
        <aside class="biblio-sidebar">
            <div class="biblio-cover-wrapper">
                <x-cover :book="$book" size="large" />
            </div>

            {{-- Consolidated Actions (Appears ONCE on the entire page) --}}
            <div class="biblio-actions-panel">
                {{-- 1. Primary Reading Action --}}
                @if($canReadOnline)
                    @if($readingProgress && $readingProgress->current_page > 0)
                        <a href="{{ route('books.reader', $book->slug) }}" class="gold biblio-btn-primary" id="btn-reading-action">
                            <span>Resume (Page {{ $readingProgress->current_page }}) &rarr;</span>
                            <small class="biblio-btn-sub">Continue Reading from Saved Progress &middot; Page {{ $readingProgress->current_page }} of {{ $readingProgress->total_pages }} ({{ round($readingProgress->percent_complete) }}% completed)</small>
                        </a>
                    @else
                        <a href="{{ route('books.reader', $book->slug) }}" class="gold biblio-btn-primary" id="btn-reading-action">
                            <x-icon name="book" size="16" />
                            <span>Read PDF Online &rarr;</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="gold biblio-btn-primary" id="btn-reading-action">
                        <x-icon name="lock" size="16" />
                        <span>Sign In to Read &rarr;</span>
                    </a>
                @endif

                {{-- 2. Secondary Save to My Library Action --}}
                <button type="button" class="outline biblio-btn-save" @click="toggleSave()" :disabled="loading" id="btn-save-action">
                    <template x-if="saved">
                        <span class="btn-inner saved"><x-icon name="check" size="15" /> Saved to My Library</span>
                    </template>
                    <template x-if="!saved">
                        <span class="btn-inner"><x-icon name="bookmark" size="15" /> Save to My Library</span>
                    </template>
                </button>

                {{-- 3. Document Availability (Communicated ONCE) --}}
                <div class="biblio-availability-indicator">
                    @if($canReadOnline)
                        <span class="availability-status permitted">
                            <span class="status-dot green"></span>
                            Online Document Access Permitted &middot; Public Digital Access
                        </span>
                    @else
                        <span class="availability-status restricted">
                            <span class="status-dot amber"></span>
                            Institutional Readers Only &middot; Institutional Reader Authentication Required
                        </span>
                    @endif
                </div>

                {{-- 4. Download Option (Communicated ONCE) --}}
                @if($canDownload && $downloadUrl)
                    <div class="biblio-download-action">
                        <a href="{{ $downloadUrl }}" target="_blank" download class="biblio-download-link" id="btn-download-doc">
                            <x-icon name="download" size="14" /> Download Document (PDF) ↗
                        </a>
                    </div>
                @elseif($book->allow_download && !auth()->check())
                    <div class="biblio-download-action">
                        <span class="biblio-download-note">
                            <x-icon name="download" size="13" /> <a href="{{ route('login') }}" class="policy-link">Sign in to download</a>
                        </span>
                    </div>
                @else
                    <div class="biblio-download-action">
                        <span class="biblio-download-note muted">
                            <x-icon name="shield" size="13" /> Online Reading Only &middot; Institutional preservation policy restricts local reproduction
                        </span>
                    </div>
                @endif
            </div>

            {{-- Admin Portal Edit Link (if Admin) --}}
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="biblio-admin-bar">
                        <a href="{{ route('admin.books.edit', $book->id) }}" class="biblio-admin-link">
                            <x-icon name="settings" size="14" />
                            <span>Edit Publication in Admin Portal &rarr;</span>
                        </a>
                    </div>
                @endif
            @endauth
        </aside>

        {{-- ============================================================
             RIGHT COLUMN: Clean Publication Hierarchy & Progressive Disclosure
             ============================================================ --}}
        <main class="biblio-main">
            {{-- Essential Publication Header --}}
            <header class="biblio-header">
                {{-- Compact Metadata Badges Row --}}
                <div class="biblio-meta-badges">
                    <span class="biblio-badge biblio-badge-type">
                        <x-icon name="{{ $book->publication_type_icon }}" size="12" />
                        {{ $book->formattedPublicationType() }}
                    </span>

                    @php $pubYear = $book->publication_year ?: $book->published_year; @endphp
                    @if(!empty($pubYear))
                        <span class="biblio-badge biblio-badge-year">{{ $pubYear }}</span>
                    @endif

                    @if($book->category)
                        <a href="{{ route('explore', ['category' => $book->category->slug]) }}" class="biblio-badge biblio-badge-category">
                            <x-icon name="folder" size="12" />
                            {{ $book->category->name }}
                        </a>
                    @endif

                    @if($book->library)
                        <a href="{{ route('libraries.show', $book->library->slug) }}" class="biblio-badge biblio-badge-library">
                            <x-icon name="collections" size="12" />
                            {{ $book->library->name }}
                        </a>
                    @endif

                    @if($book->series)
                        <a href="{{ route('explore', ['q' => $book->series, 'search_by' => 'series']) }}" class="biblio-badge biblio-badge-series">
                            <x-icon name="bookmark" size="12" />
                            {{ $book->series }}{{ $book->series_number ? ' #' . $book->series_number : '' }}
                        </a>
                    @endif
                </div>

                {{-- Prominent Title & Subtitle (Displayed ONCE) --}}
                <h1 class="biblio-clean-title">{{ $book->title }}</h1>
                @if(!empty($book->subtitle))
                    <p class="biblio-clean-subtitle">{{ $book->subtitle }}</p>
                @endif

                {{-- Prominent Authors & Contributors (Displayed ONCE) --}}
                <div class="biblio-authors-container">
                    @if($book->authors && $book->authors->count() > 0)
                        <div class="biblio-contributors-chips">
                            @foreach($book->authors as $author)
                                <a href="{{ route('explore', ['q' => $author->name, 'search_by' => 'author']) }}" class="biblio-contributor-chip" title="Filter catalogue by {{ $author->name }}">
                                    <x-icon name="feather" size="12" />
                                    <span class="contributor-name">{{ $author->name }}</span>
                                    <span class="contributor-role-tag">{{ ucwords(str_replace('_', ' ', $author->pivot->role ?? 'Author')) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @elseif(!empty($book->author_name))
                        <div class="biblio-single-author">
                            <span class="by-label">By</span>
                            <a href="{{ route('explore', ['q' => $book->author_name, 'search_by' => 'author']) }}" class="biblio-author-link" title="Search all publications by {{ $book->author_name }}">
                                {{ $book->author_name }}
                            </a>
                        </div>
                    @endif
                </div>
            </header>

            {{-- Description / Abstract Section --}}
            <section class="biblio-abstract-block">
                <h3 class="biblio-clean-subhead">Executive Abstract</h3>
                @if(!empty($book->description))
                    <div class="biblio-clean-abstract">
                        {!! nl2br(e($book->description)) !!}
                    </div>
                @else
                    <p class="biblio-empty-text">No formal abstract or executive summary has been catalogued for this publication.</p>
                @endif
            </section>

            {{-- Compact Publication Metadata Summary (Displayed ONCE) --}}
            @php
                $pCount = $book->pages_count ?: ($book->primaryPdf?->page_count);
                $fmt = $book->primaryPdf ? ('PDF' . ($book->primaryPdf->formatted_size ? ' (' . $book->primaryPdf->formatted_size . ')' : '')) : ($book->files->first() ? strtoupper($book->files->first()->file_type) : null);
            @endphp
            <section class="biblio-compact-specs">
                @if(!empty($book->publisher))
                    <div class="spec-item">
                        <span class="spec-label">Publisher</span>
                        <a href="{{ route('explore', ['q' => $book->publisher, 'search_by' => 'publisher']) }}" class="spec-value link" title="Search by publisher">
                            {{ $book->publisher }} ↗
                        </a>
                    </div>
                @endif

                @if(!empty($pubYear))
                    <div class="spec-item">
                        <span class="spec-label">Published</span>
                        <span class="spec-value">{{ $pubYear }}</span>
                    </div>
                @endif

                @if(!empty($pCount))
                    <div class="spec-item">
                        <span class="spec-label">Pages</span>
                        <span class="spec-value">{{ $pCount }} {{ Str::plural('page', $pCount) }}</span>
                    </div>
                @endif

                @if(!empty($fmt))
                    <div class="spec-item">
                        <span class="spec-label">Format</span>
                        <span class="spec-value">{{ $fmt }}</span>
                    </div>
                @endif

                @if(!empty($book->language))
                    <div class="spec-item">
                        <span class="spec-label">Language</span>
                        <span class="spec-value">{{ $book->language }}</span>
                    </div>
                @endif
            </section>

            {{-- Subjects & Keywords (Rendered seamlessly without separate heavy cards) --}}
            @php
                $allSubjects = collect([$book->category])->filter();
                if ($book->categories && $book->categories->isNotEmpty()) {
                    $allSubjects = $allSubjects->merge($book->categories)->unique('id');
                }
            @endphp
            @if($allSubjects->isNotEmpty() || ($book->tags && $book->tags->isNotEmpty()))
                <section class="biblio-inline-taxonomies">
                    @if($allSubjects->isNotEmpty())
                        <div class="taxonomy-row">
                            <span class="taxonomy-label">Disciplines:</span>
                            <div class="taxonomy-chips">
                                @foreach($allSubjects as $subj)
                                    <a href="{{ route('explore', ['category' => $subj->slug]) }}" class="biblio-subject-chip" title="View all {{ $subj->name }} publications">
                                        <x-icon name="folder" size="12" />
                                        <span>{{ $subj->name }}</span>
                                        @if(isset($subj->pivot) && $subj->pivot->is_primary)
                                            <span class="chip-primary-badge">Primary</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($book->tags && $book->tags->isNotEmpty())
                        <div class="taxonomy-row">
                            <span class="taxonomy-label">Keywords:</span>
                            <div class="taxonomy-chips">
                                @foreach($book->tags as $tag)
                                    <a href="{{ route('explore', ['q' => $tag->name, 'search_by' => 'tags']) }}" class="biblio-tag-chip" title="Filter by tag: #{{ $tag->name }}">
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Progressive Bibliographic Disclosure (Expandable Record & Citation) --}}
            <section class="biblio-disclosure-container" x-data="{ expanded: false }">
                <button type="button" class="biblio-disclosure-trigger" @click="expanded = !expanded" :aria-expanded="expanded">
                    <div class="trigger-left">
                        <x-icon name="book" size="15" />
                        <span class="trigger-title">Full Bibliographic Record &amp; Citation</span>
                    </div>
                    <span class="trigger-toggle" x-text="expanded ? 'Hide Details ↑' : 'Show Full Record ↓'">Show Full Record ↓</span>
                </button>

                <div class="biblio-disclosure-content" x-show="expanded" x-cloak x-transition>
                    <div class="biblio-details-grid">
                        <div class="detail-row">
                            <span class="detail-label">Publication Type</span>
                            <span class="detail-value">{{ $book->formattedPublicationType() }}</span>
                        </div>

                        @if(!empty($book->publisher))
                            <div class="detail-row">
                                <span class="detail-label">Publisher</span>
                                <span class="detail-value">
                                    <a href="{{ route('explore', ['q' => $book->publisher, 'search_by' => 'publisher']) }}" class="biblio-link">
                                        {{ $book->publisher }} ↗
                                    </a>
                                </span>
                            </div>
                        @endif

                        @if(!empty($book->edition))
                            <div class="detail-row">
                                <span class="detail-label">Edition</span>
                                <span class="detail-value">{{ $book->edition }}</span>
                            </div>
                        @endif

                        @if(!empty($book->isbn))
                            <div class="detail-row">
                                <span class="detail-label">ISBN-10</span>
                                <span class="detail-value font-mono">{{ $book->isbn }}</span>
                            </div>
                        @endif

                        @if(!empty($book->isbn13))
                            <div class="detail-row">
                                <span class="detail-label">ISBN-13</span>
                                <span class="detail-value font-mono">{{ $book->isbn13 }}</span>
                            </div>
                        @endif

                        @if(!empty($book->doi))
                            <div class="detail-row">
                                <span class="detail-label">Digital Object Identifier (DOI)</span>
                                <span class="detail-value">
                                    <a href="https://doi.org/{{ $book->doi }}" target="_blank" rel="noopener noreferrer" class="biblio-link font-mono">
                                        {{ $book->doi }} ↗
                                    </a>
                                </span>
                            </div>
                        @endif

                        @if(!empty($book->series))
                            <div class="detail-row">
                                <span class="detail-label">Series / Monograph Collection</span>
                                <span class="detail-value">
                                    <a href="{{ route('explore', ['q' => $book->series, 'search_by' => 'series']) }}" class="biblio-link">
                                        {{ $book->series }}{{ $book->series_number ? ' · Volume ' . $book->series_number : '' }} ↗
                                    </a>
                                </span>
                            </div>
                        @endif

                        @if($book->library)
                            <div class="detail-row">
                                <span class="detail-label">Digital Library Repository</span>
                                <span class="detail-value">
                                    <a href="{{ route('libraries.show', $book->library->slug) }}" class="biblio-link">
                                        {{ $book->library->name }} ↗
                                    </a>
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Citation Card (Single logical location) --}}
                    <div class="biblio-citation-card">
                        <div class="citation-head">
                            <div class="citation-title">
                                <x-icon name="clipboard" size="13" />
                                <span>Bibliographic Citation (APA/Executive Standard)</span>
                            </div>
                            <button type="button" @click="copyCitation()" class="citation-copy-btn" title="Copy formatted citation to clipboard">
                                <template x-if="citationCopied">
                                    <span class="copy-feedback-active"><x-icon name="check" size="12" /> Copied!</span>
                                </template>
                                <template x-if="!citationCopied">
                                    <span class="copy-feedback-idle"><x-icon name="clipboard" size="12" /> Copy Citation</span>
                                </template>
                            </button>
                        </div>
                        <div id="citation-text" class="citation-text-content">
                            {{ $book->formattedCitation() }}
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    {{-- ============================================================
         RELATED PUBLICATIONS (Clean Full-Width Grid)
         ============================================================ --}}
    @if($relatedBooks && $relatedBooks->count() > 0)
        <section class="biblio-related-section" id="section-related-publications">
            <div class="section-head">
                <div>
                    <div class="eyebrow">Institutional Discoveries</div>
                    <h2>Related Publications @if($book->category)<span class="related-sub-head">in {{ $book->category->name }}</span>@endif</h2>
                </div>
                @if($book->category)
                    <a href="{{ route('explore', ['category' => $book->category->slug]) }}" class="link">
                        Explore Subject Catalogue &rarr;
                    </a>
                @else
                    <a href="{{ route('explore') }}" class="link">
                        Explore Full Catalogue &rarr;
                    </a>
                @endif
            </div>

            <x-books-grid :books="$relatedBooks" />
        </section>
    @endif
</section>
@endsection
