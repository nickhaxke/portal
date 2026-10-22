@extends('layouts.admin')

@section('title', 'Edit: ' . $book->title)
@section('header_title', 'Edit Bibliographic Record')

@section('content')
<div x-data="{
    title: '{{ addslashes(old('title', $book->title)) }}',
    author: '{{ addslashes(old('author_name', $book->author_name)) }}',
    status: '{{ old('status', $book->status) }}',
    visibility: '{{ old('visibility', $book->visibility ?? 'public') }}',
    coverBg: '{{ old('cover_bg', $book->cover_bg ?? '#102946') }}',
    coverFg: '{{ old('cover_fg', $book->cover_fg ?? '#ffffff') }}',
    hasCoverImage: {{ $book->cover_image ? 'true' : 'false' }},
    coverPreviewUrl: '{{ $book->cover_url ?: '' }}',
    removeCover: false,
    hasPdfFile: false,
    pdfFileName: '',
    pdfFileSize: '',
    saveAction: '{{ $book->status === 'draft' ? 'draft' : 'publish' }}',
    isUploading: false,
    uploadProgress: 0,
    uploadStatus: '',
    uploadSubtext: '',
    errorMessage: '',
    fieldErrors: {},
    duplicateWarning: '{{ session('duplicate_warning') }}',
    confirmDuplicate: false,

    handleImageChange(e) {
        const file = e.target.files[0];
        if (file) {
            this.hasCoverImage = true;
            this.removeCover = false;
            this.coverPreviewUrl = URL.createObjectURL(file);
        }
    },
    handlePdfChange(e) {
        const file = e.target.files[0];
        if (file) {
            this.hasPdfFile = true;
            this.pdfFileName = file.name;
            this.pdfFileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        } else {
            this.hasPdfFile = false;
            this.pdfFileName = '';
            this.pdfFileSize = '';
        }
    },
    setPalette(bg, fg) {
        this.coverBg = bg;
        this.coverFg = fg;
    },
    submitWithAction(action, e) {
        this.saveAction = action;
        if (action === 'draft') {
            this.status = 'draft';
        } else if (action === 'publish' && this.status === 'draft') {
            this.status = 'published';
        }
    },
    submitForm(e) {
        e.preventDefault();
        const form = e.target;
        this.errorMessage = '';
        this.fieldErrors = {};
        this.isUploading = true;
        this.uploadProgress = 0;
        this.uploadStatus = this.saveAction === 'draft' ? 'Saving publication updates as draft...' : 'Saving and publishing updates...';
        this.uploadSubtext = 'Establishing secure transfer...';

        const formData = new FormData(form);
        formData.set('save_action', this.saveAction);
        formData.set('status', this.status);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = (evt) => {
            if (evt.lengthComputable) {
                const percent = Math.min(99, Math.round((evt.loaded / evt.total) * 100));
                this.uploadProgress = percent;
                const loadedMB = (evt.loaded / (1024 * 1024)).toFixed(1);
                const totalMB = (evt.total / (1024 * 1024)).toFixed(1);
                this.uploadStatus = `Uploading updates (${percent}%)...`;
                this.uploadSubtext = `${loadedMB} MB of ${totalMB} MB transferred — keep this tab open`;
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                this.uploadProgress = 100;
                this.uploadStatus = 'Verifying publication record...';
                this.uploadSubtext = 'Finalizing updates and updating search index...';
                try {
                    const resp = JSON.parse(xhr.responseText);
                    window.location.href = resp.redirect || '{{ route('admin.books.index') }}';
                } catch (err) {
                    window.location.href = '{{ route('admin.books.index') }}';
                }
            } else {
                this.isUploading = false;
                try {
                    const errResp = JSON.parse(xhr.responseText);
                    if (errResp.warning) {
                        this.duplicateWarning = errResp.message;
                        this.errorMessage = errResp.message;
                    } else if (errResp.errors) {
                        this.fieldErrors = errResp.errors;
                        const msgs = Object.values(errResp.errors).flat().join(' ');
                        this.errorMessage = msgs || 'Validation failed. Please verify form values.';
                    } else {
                        this.errorMessage = errResp.message || 'Server error occurred during update.';
                    }
                } catch (err) {
                    if (xhr.status === 413) {
                        this.errorMessage = 'File exceeds web server maximum upload limit. Please verify php.ini upload_max_filesize.';
                    } else {
                        this.errorMessage = `Upload failed with status ${xhr.status}. Please check your connection.`;
                    }
                }
            }
        };

        xhr.onerror = () => {
            this.isUploading = false;
            this.errorMessage = 'Network connection failed during update. Please check connectivity and try again.';
        };

        xhr.send(formData);
    }
}">
    <!-- Top Navigation Bar -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('admin.books.index') }}" class="plain" style="font-size: 13.5px; font-weight: 600; color: var(--navy); display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to Publications Catalogue
        </a>
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('books.show', $book->slug) }}" target="_blank" class="outline" style="padding: 7px 14px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                <x-icon name="external-link" size="13" /> View Public Page ↗
            </a>
            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; background: #e2e8f0; color: var(--navy); padding: 3px 8px; border-radius: 4px;">
                RECORD ID #{{ $book->id }}
            </span>
        </div>
    </div>

    <!-- Duplicate Warning Banner -->
    <template x-if="duplicateWarning">
        <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(185,130,39,0.08);">
            <div style="font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 6px; color: #b45309;">
                <x-icon name="alert-triangle" size="18" /> Potential Duplicate Publication Detected
            </div>
            <p style="margin: 0 0 12px; font-size: 13px; line-height: 1.5;" x-text="duplicateWarning"></p>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #78350f;">
                <input type="checkbox" name="confirm_duplicate" value="1" x-model="confirmDuplicate" style="width: 16px; height: 16px;">
                I confirm this publication is intentional and authorize saving this record.
            </label>
        </div>
    </template>

    <!-- Global Blade Validation Errors Banner -->
    @if ($errors->any())
        <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
            <div style="font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <x-icon name="close" size="16" /> Validation Errors Encountered:
            </div>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.books.update', $book->id) }}" enctype="multipart/form-data" @submit="submitForm($event)">
        @csrf
        @method('PUT')
        <input type="hidden" name="save_action" :value="saveAction">
        <input type="hidden" name="confirm_duplicate" :value="confirmDuplicate ? '1' : '0'">

        <div class="cataloguing-layout">
            <!-- ============================================================ -->
            <!-- MAIN COLUMN: 6 LOGICAL CATALOGUING SECTIONS -->
            <!-- ============================================================ -->
            <div>
                <!-- SECTION 1: BASIC INFORMATION -->
                <div class="cat-section" id="sec-basic-info">
                    <div class="cat-section-header">
                        <div>
                            <span class="cat-section-badge">Section 1 of 6 &bull; Core Bibliographic Manifest</span>
                            <h3 class="cat-section-title">Basic Information</h3>
                            <p class="cat-section-desc">Primary title, monograph subtitle, document type, and executive summary.</p>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="cat-field-group">
                        <label for="title" class="cat-label">
                            <span>Publication Title <span class="cat-req">*</span></span>
                            <span class="cat-opt">Full formal title</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            x-model="title" 
                            value="{{ old('title', $book->title) }}" 
                            class="field" 
                            required 
                            placeholder="e.g. Sovereign Institutional Governance in Strategic Systems"
                            style="width: 100%; font-size: 15px; font-weight: 600;"
                        >
                        @error('title') <span class="cat-error"><x-icon name="close" size="12" /> {{ $message }}</span> @enderror
                        <template x-if="fieldErrors['title']">
                            <span class="cat-error" x-text="fieldErrors['title'][0]"></span>
                        </template>
                    </div>

                    <!-- Subtitle & Publication Type -->
                    <div class="cat-grid-2">
                        <div class="cat-field-group">
                            <label for="subtitle" class="cat-label">
                                <span>Subtitle / Monograph Qualifier</span>
                                <span class="cat-opt">Optional</span>
                            </label>
                            <input 
                                type="text" 
                                id="subtitle" 
                                name="subtitle" 
                                value="{{ old('subtitle', $book->subtitle) }}" 
                                class="field" 
                                placeholder="e.g. Decision Architecture and Strategic Risk"
                                style="width: 100%;"
                            >
                            @error('subtitle') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="publication_type" class="cat-label">
                                <span>Publication Type <span class="cat-req">*</span></span>
                            </label>
                            <select id="publication_type" name="publication_type" class="field" style="width: 100%;">
                                @foreach($publicationTypes as $typeKey => $typeData)
                                    <option value="{{ $typeKey }}" {{ old('publication_type', $book->publication_type) === $typeKey ? 'selected' : '' }}>
                                        {{ $typeData['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('publication_type') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Description / Abstract -->
                    <div class="cat-field-group">
                        <label for="description" class="cat-label">
                            <span>Description / Executive Abstract</span>
                            <span class="cat-opt">Detailed bibliographic abstract</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="4" 
                            class="field" 
                            placeholder="Provide an executive summary detailing the research foundations, core strategic methodologies, and actionable takeaways..."
                            style="width: 100%; line-height: 1.6;"
                        >{{ old('description', $book->description) }}</textarea>
                        <span class="cat-hint">Formatted abstracts are displayed prominently on the public and institutional publication details pages.</span>
                        @error('description') <span class="cat-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- SECTION 2: AUTHORS & CONTRIBUTORS -->
                <div class="cat-section" id="sec-authors">
                    <div class="cat-section-header">
                        <div>
                            <span class="cat-section-badge">Section 2 of 6 &bull; Intellectual Attribution</span>
                            <h3 class="cat-section-title">Authors & Contributors</h3>
                            <p class="cat-section-desc">Designate primary author, contributing co-authors, and editorial curators.</p>
                        </div>
                    </div>

                    <!-- Primary Author -->
                    <div class="cat-field-group">
                        <label for="author_name" class="cat-label">
                            <span>Primary / Lead Author <span class="cat-req">*</span></span>
                            <span class="cat-opt">Lead attribution</span>
                        </label>
                        <input 
                            type="text" 
                            id="author_name" 
                            name="author_name" 
                            x-model="author" 
                            value="{{ old('author_name', $book->author_name) }}" 
                            class="field" 
                            required 
                            list="existing-authors"
                            placeholder="e.g. Dr. Victoria Sterling"
                            style="width: 100%; font-size: 14px;"
                        >
                        <datalist id="existing-authors">
                            @foreach($authors as $a)
                                <option value="{{ $a->name }}">
                            @endforeach
                        </datalist>
                        <span class="cat-hint">Lead author appears in primary citations, search cards, and bibliographic indices.</span>
                        @error('author_name') <span class="cat-error"><x-icon name="close" size="12" /> {{ $message }}</span> @enderror
                        <template x-if="fieldErrors['author_name']">
                            <span class="cat-error" x-text="fieldErrors['author_name'][0]"></span>
                        </template>
                    </div>

                    <!-- Multi-Contributor Selectors -->
                    <div class="cat-grid-2">
                        <div class="cat-field-group">
                            <x-multi-select-dropdown 
                                name="co_author_ids[]"
                                label="Contributing Co-Authors"
                                placeholder="Select contributing co-authors..."
                                :items="$authors"
                                :selected="old('co_author_ids', $coAuthorIds ?? [])"
                                help="Assigned with 'Co-Author' bibliographic badges."
                            />
                            @error('co_author_ids') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <x-multi-select-dropdown 
                                name="editor_ids[]"
                                label="Editors & Translators"
                                placeholder="Select editors or translators..."
                                :items="$authors"
                                :selected="old('editor_ids', $editorIds ?? [])"
                                help="Assigned with 'Editor' / 'Translator' role chips."
                            />
                            @error('editor_ids') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: CLASSIFICATION & DISCOVERY -->
                <div class="cat-section" id="sec-classification">
                    <div class="cat-section-header">
                        <div>
                            <span class="cat-section-badge">Section 3 of 6 &bull; Taxonomy & Discovery</span>
                            <h3 class="cat-section-title">Classification & Discovery</h3>
                            <p class="cat-section-desc">Assign digital library repository, knowledge disciplines, and search tags.</p>
                        </div>
                    </div>

                    <div class="cat-grid-2">
                        <!-- Digital Library -->
                        <div class="cat-field-group">
                            <label for="library_id" class="cat-label">
                                <span>Assigned Digital Library <span class="cat-req">*</span></span>
                            </label>
                            <select id="library_id" name="library_id" class="field" style="width: 100%;">
                                @foreach($libraries as $lib)
                                    <option value="{{ $lib->id }}" {{ old('library_id', $book->library_id) == $lib->id ? 'selected' : '' }}>
                                        {{ $lib->name }} ({{ ucfirst($lib->visibility) }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="cat-hint">Governs institutional library membership and collection segregation.</span>
                            @error('library_id') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <!-- Primary Discipline -->
                        <div class="cat-field-group">
                            <label for="category_id" class="cat-label">
                                <span>Primary Knowledge Discipline <span class="cat-req">*</span></span>
                            </label>
                            <select id="category_id" name="category_id" class="field" required style="width: 100%;">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $book->category_id) == $category->id ? 'selected' : '' }} style="font-weight: 700;">
                                        {{ $category->name }}
                                    </option>
                                    @if($category->children)
                                        @foreach($category->children as $child)
                                            <option value="{{ $child->id }}" {{ old('category_id', $book->category_id) == $child->id ? 'selected' : '' }}>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&bull; {{ $child->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                            @error('category_id') <span class="cat-error"><x-icon name="close" size="12" /> {{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Secondary Classifications -->
                    <div class="cat-field-group">
                        <x-multi-select-dropdown 
                            name="secondary_category_ids[]"
                            label="Secondary Disciplines / Interdisciplinary Categories"
                            placeholder="Select secondary disciplines..."
                            :items="$allCategories"
                            :selected="old('secondary_category_ids', $book->categories->pluck('id')->all())"
                            help="Enables cross-disciplinary recommendation and faceted catalogue discovery."
                        />
                        @error('secondary_category_ids') <span class="cat-error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tags & Keywords -->
                    <div class="cat-field-group">
                        <label for="tags" class="cat-label">
                            <span>Topical Keywords & Subject Headings</span>
                            <span class="cat-opt">Comma-separated</span>
                        </label>
                        <input 
                            type="text" 
                            id="tags" 
                            name="tags" 
                            value="{{ old('tags', $book->tags->pluck('name')->implode(', ')) }}" 
                            class="field" 
                            placeholder="e.g. Strategic Foresight, Geopolitics, Board Architecture, Economics"
                            style="width: 100%;"
                        >
                        @if(isset($tags) && $tags->count() > 0)
                            <div style="margin-top: 6px; font-size: 11.5px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                <span style="font-weight: 600;">Suggested tags:</span>
                                @foreach($tags->take(10) as $t)
                                    <span class="badge" style="cursor: pointer; background: #f1f5f9; color: var(--navy); border: 1px solid #e2e8f0; font-size: 11px;" onclick="let el = document.getElementById('tags'); el.value = el.value ? el.value + ', {{ $t->name }}' : '{{ $t->name }}';">
                                        + {{ $t->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @error('tags') <span class="cat-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- SECTION 4: PUBLISHING METADATA -->
                <div class="cat-section" id="sec-publishing">
                    <div class="cat-section-header">
                        <div>
                            <span class="cat-section-badge">Section 4 of 6 &bull; Bibliographic Specifications</span>
                            <h3 class="cat-section-title">Publishing Metadata & Identifiers</h3>
                            <p class="cat-section-desc">Publisher, imprint, publication date, series details, and international standard identifiers.</p>
                        </div>
                    </div>

                    <!-- Publisher, Year & Edition -->
                    <div class="cat-grid-3">
                        <div class="cat-field-group">
                            <label for="publisher" class="cat-label">
                                <span>Publisher / Press</span>
                                <span class="cat-opt">Imprint</span>
                            </label>
                            <input 
                                type="text" 
                                id="publisher" 
                                name="publisher" 
                                value="{{ old('publisher', $book->publisher) }}" 
                                class="field" 
                                placeholder="Executive Intelligence Press"
                                style="width: 100%;"
                            >
                            @error('publisher') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="published_year" class="cat-label">
                                <span>Publication Year</span>
                                <span class="cat-opt">YYYY</span>
                            </label>
                            <input 
                                type="number" 
                                id="published_year" 
                                name="published_year" 
                                value="{{ old('published_year', $book->published_year ?: $book->publication_year) }}" 
                                class="field" 
                                min="1900" 
                                max="2100"
                                style="width: 100%;"
                            >
                            @error('published_year') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="edition" class="cat-label">
                                <span>Edition / Revision</span>
                                <span class="cat-opt">e.g. 1st Edition</span>
                            </label>
                            <input 
                                type="text" 
                                id="edition" 
                                name="edition" 
                                value="{{ old('edition', $book->edition) }}" 
                                class="field" 
                                placeholder="1st Global Edition"
                                style="width: 100%;"
                            >
                            @error('edition') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Series, Volume & Language -->
                    <div class="cat-grid-3">
                        <div class="cat-field-group">
                            <label for="series" class="cat-label">
                                <span>Series / Monograph Title</span>
                                <span class="cat-opt">Optional</span>
                            </label>
                            <input 
                                type="text" 
                                id="series" 
                                name="series" 
                                value="{{ old('series', $book->series) }}" 
                                class="field" 
                                placeholder="Strategic Monograph Series"
                                style="width: 100%;"
                            >
                            @error('series') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="series_number" class="cat-label">
                                <span>Series Volume / #</span>
                                <span class="cat-opt">Numeric</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.1" 
                                id="series_number" 
                                name="series_number" 
                                value="{{ old('series_number', $book->series_number) }}" 
                                class="field" 
                                placeholder="1"
                                style="width: 100%;"
                            >
                            @error('series_number') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="language" class="cat-label">
                                <span>Publication Language</span>
                            </label>
                            <input 
                                type="text" 
                                id="language" 
                                name="language" 
                                value="{{ old('language', $book->language ?? 'English') }}" 
                                class="field" 
                                placeholder="English"
                                style="width: 100%;"
                            >
                            @error('language') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- ISBN-10, ISBN-13 & DOI -->
                    <div class="cat-grid-3">
                        <div class="cat-field-group">
                            <label for="isbn" class="cat-label">
                                <span>ISBN-10</span>
                                <span class="cat-opt">Standard</span>
                            </label>
                            <input 
                                type="text" 
                                id="isbn" 
                                name="isbn" 
                                value="{{ old('isbn', $book->isbn) }}" 
                                class="field" 
                                placeholder="0-123456-78-9"
                                style="width: 100%; font-family: monospace;"
                            >
                            @error('isbn') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="isbn13" class="cat-label">
                                <span>ISBN-13</span>
                                <span class="cat-opt">EAN-13</span>
                            </label>
                            <input 
                                type="text" 
                                id="isbn13" 
                                name="isbn13" 
                                value="{{ old('isbn13', $book->isbn13) }}" 
                                class="field" 
                                placeholder="978-0-123456-78-9"
                                style="width: 100%; font-family: monospace;"
                            >
                            @error('isbn13') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="cat-field-group">
                            <label for="doi" class="cat-label">
                                <span>Digital Object Identifier (DOI)</span>
                            </label>
                            <input 
                                type="text" 
                                id="doi" 
                                name="doi" 
                                value="{{ old('doi', $book->doi) }}" 
                                class="field" 
                                placeholder="10.1000/xyz123"
                                style="width: 100%; font-family: monospace;"
                            >
                            @error('doi') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 5: DOCUMENTS & COVER MANIFESTATION -->
                <div class="cat-section" id="sec-documents">
                    <div class="cat-section-header">
                        <div>
                            <span class="cat-section-badge">Section 5 of 6 &bull; Digital Assets & Manifestation</span>
                            <h3 class="cat-section-title">Documents & Cover Manifestation</h3>
                            <p class="cat-section-desc">Manage attached publication PDF/EPUB, page specifications, and cover presentation.</p>
                        </div>
                    </div>

                    <!-- Current Attached File Info Card -->
                    <div style="background: #fafaf9; border: 1px solid var(--border); border-radius: 8px; padding: 18px 20px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong style="color: var(--navy); font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
                                <x-icon name="document" size="18" /> Current Attached Document:
                            </strong>
                            @if($activeFile)
                                <span class="badge badge-published" style="display: inline-flex; align-items: center; gap: 5px;">
                                    <x-icon name="check" size="12" /> Active (Version {{ $activeFile->version }})
                                </span>
                            @else
                                <span class="badge badge-draft">Sample Edition Active</span>
                            @endif
                        </div>

                        @if($activeFile)
                            <div style="font-size: 12.5px; color: var(--navy); margin-bottom: 8px; font-weight: 500;">
                                {{ $activeFile->file_name }} &bull; {{ number_format($activeFile->file_size / 1024 / 1024, 2) }} MB &bull; {{ strtoupper($activeFile->file_type) }}
                            </div>
                        @endif

                        <label for="pdf_file" style="display: block; font-size: 12px; font-weight: 600; color: var(--navy); margin-top: 10px; margin-bottom: 6px;">
                            Upload replacement PDF / EPUB:
                        </label>
                        <input 
                            type="file" 
                            id="pdf_file" 
                            name="pdf_file" 
                            accept=".pdf,.epub,application/pdf,application/epub+zip"
                            @change="handlePdfChange($event)"
                            style="font-size: 13px;"
                        >
                        <span class="cat-hint" style="display: block; margin-top: 4px;">
                            Leave empty to keep current file intact. Uploading a new file automatically archives the prior version and queues metadata extraction.
                        </span>
                        <template x-if="hasPdfFile">
                            <div style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 12px; border-radius: 6px; font-size: 12px; color: #065f46; font-weight: 600;">
                                <x-icon name="check" size="13" /> New replacement ready: <span x-text="pdfFileName"></span> (<span x-text="pdfFileSize"></span>)
                            </div>
                        </template>
                        @error('pdf_file') <span class="cat-error"><x-icon name="close" size="12" /> {{ $message }}</span> @enderror
                    </div>

                    <div class="cat-grid-2">
                        <!-- Pages Count -->
                        <div class="cat-field-group">
                            <label for="pages_count" class="cat-label">
                                <span>Total Catalogued Page Count</span>
                                <span class="cat-opt">Integer</span>
                            </label>
                            <input 
                                type="number" 
                                id="pages_count" 
                                name="pages_count" 
                                value="{{ old('pages_count', $book->pages_count) }}" 
                                class="field" 
                                min="1"
                                style="width: 100%;"
                            >
                            @error('pages_count') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <!-- Download Policy -->
                        <div class="cat-field-group">
                            <label class="cat-label">
                                <span>Document Download Permissions</span>
                            </label>
                            <div style="padding-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--navy); font-weight: 500;">
                                    <input type="checkbox" name="allow_download" value="1" {{ old('allow_download', $book->allow_download) ? 'checked' : '' }} style="width: 16px; height: 16px;">
                                    Authorize readers to download local copy
                                </label>
                                <span class="cat-hint">When unchecked, preservation policy enforces Online Reading Only.</span>
                            </div>
                            @error('allow_download') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 6: PUBLISHING & ACCESS GOVERNANCE -->
                <div class="cat-section" id="sec-access">
                    <div class="cat-section-header">
                        <div>
                            <span class="cat-section-badge">Section 6 of 6 &bull; Lifecycle & Governance</span>
                            <h3 class="cat-section-title">Publishing & Access Governance</h3>
                            <p class="cat-section-desc">Manage publication lifecycle status, reader access clearance, and homepage feature flags.</p>
                        </div>
                    </div>

                    <div class="cat-grid-2">
                        <!-- Status -->
                        <div class="cat-field-group">
                            <label for="status" class="cat-label">
                                <span>Lifecycle Status <span class="cat-req">*</span></span>
                            </label>
                            <select id="status" name="status" class="field" x-model="status" style="width: 100%;">
                                <option value="published">Published (Live & Searchable)</option>
                                <option value="draft">Draft (Saved for Editorial Review)</option>
                                <option value="archived">Archived (Preserved / Hidden from Public)</option>
                            </select>
                            <span class="cat-hint">Draft publications remain invisible to visitors on public explore catalogues.</span>
                            @error('status') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        <!-- Visibility -->
                        <div class="cat-field-group">
                            <label for="visibility" class="cat-label">
                                <span>Access Clearance Level <span class="cat-req">*</span></span>
                            </label>
                            <select id="visibility" name="visibility" class="field" x-model="visibility" style="width: 100%;">
                                <option value="public">Public (Open to All Visitors)</option>
                                <option value="restricted">Restricted (Authenticated Readers Only)</option>
                                <option value="private">Private (Institutional Administrators Only)</option>
                            </select>
                            <span class="cat-hint">Enforces Institutional Single Sign-On and Access Clearance checks.</span>
                            @error('visibility') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Featured Flag -->
                    <div style="margin-top: 16px; padding: 14px 18px; background: #fffdf5; border: 1px solid #fae8b8; border-radius: 8px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--navy); font-weight: 600; font-size: 13.5px;">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $book->is_featured) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                            Feature this publication prominently on the portal homepage
                        </label>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- RIGHT STICKY SIDEBAR: ACTIONS, COVER PREVIEW & CHECKLIST -->
            <!-- ============================================================ -->
            <div>
                <div class="cat-sidebar">
                    <!-- Actions Card -->
                    <div class="cat-actions-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--navy);">Cataloguing State</span>
                            <span 
                                class="cat-status-pill" 
                                :class="status"
                                x-text="status.toUpperCase()"
                            ></span>
                        </div>

                        <!-- Visibility Clearance Indicator -->
                        <div style="margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--muted);">
                            <span>Access Clearance:</span>
                            <span style="font-weight: 600; color: var(--navy);" x-text="visibility.charAt(0).toUpperCase() + visibility.slice(1)"></span>
                        </div>

                        <!-- Progress indicator when uploading -->
                        <div x-show="isUploading" style="margin-bottom: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 600; color: var(--navy);" x-text="uploadStatus">Uploading updates...</span>
                                <span style="font-size: 12px; font-weight: 700; color: var(--gold);" x-text="uploadProgress + '%'"></span>
                            </div>
                            <div style="width: 100%; height: 7px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-bottom: 6px;">
                                <div style="height: 100%; background: linear-gradient(90deg, #102946, #c29b38); transition: width 0.2s ease;" :style="'width: ' + uploadProgress + '%'"></div>
                            </div>
                            <div style="font-size: 11px; color: var(--muted); text-align: center;" x-text="uploadSubtext"></div>
                        </div>

                        <!-- Error Message if failed -->
                        <template x-if="errorMessage">
                            <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; border-radius: 6px; padding: 10px 12px; margin-bottom: 16px; font-size: 12px;">
                                <div style="font-weight: 700; margin-bottom: 2px; display: flex; align-items: center; gap: 4px;">
                                    <x-icon name="close" size="13" /> Update Issue:
                                </div>
                                <div x-text="errorMessage"></div>
                            </div>
                        </template>

                        <!-- Action Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 10px;" x-show="!isUploading">
                            <button 
                                type="submit" 
                                class="cat-action-btn-primary" 
                                @click="submitWithAction('publish', $event)"
                            >
                                <x-icon name="check" size="16" /> Save Changes & Publish &rarr;
                            </button>
                            <button 
                                type="submit" 
                                class="cat-action-btn-secondary" 
                                @click="submitWithAction('draft', $event)"
                            >
                                Save Changes as Draft
                            </button>
                        </div>
                    </div>

                    <!-- Live Cover Manifestation Card -->
                    <div class="cat-actions-card">
                        <h4 style="font: 700 16px 'Playfair Display', serif; margin: 0 0 14px; color: var(--navy);">Cover Artwork Manifest</h4>

                        <!-- Live Book Card Preview -->
                        <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                            <div 
                                class="cover" 
                                :style="(hasCoverImage && !removeCover)
                                    ? `height: 250px; width: 175px; background-image: url('${coverPreviewUrl}'); background-size: cover; background-position: center; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(16,41,70,0.2); border-radius: 4px;` 
                                    : `height: 250px; width: 175px; background: ${coverBg}; color: ${coverFg}; border-radius: 4px; box-shadow: 0 10px 25px -5px rgba(16,41,70,0.2); padding: 16px;`"
                            >
                                <div x-show="!hasCoverImage || removeCover" style="display: flex; flex-direction: column; height: 100%; justify-content: space-between; text-align: left; width: 100%;">
                                    <small style="letter-spacing: 0.05em; font-size: 9.5px; font-weight: 700; opacity: 0.85;">EXECUTIVE E-LIBRARY</small>
                                    <b style="font-size: 15px; line-height: 1.25; font-family: 'Playfair Display', serif;" x-text="title || 'Publication Title'"></b>
                                    <small style="font-size: 11px; font-weight: 500; opacity: 0.9;" x-text="author || 'Author Name'"></small>
                                </div>
                            </div>

                            <template x-if="hasCoverImage && !removeCover">
                                <span style="font-size: 11px; color: #166534; background: #dcfce7; padding: 2px 7px; border-radius: 4px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <x-icon name="check" size="12" /> Custom Cover Active
                                </span>
                            </template>
                        </div>

                        <!-- Replace Cover Artwork -->
                        <div class="cat-field-group">
                            <label for="cover_image" class="cat-label">
                                <span>Replace Cover Artwork</span>
                                <span class="cat-opt">Max 5MB</span>
                            </label>
                            <input 
                                type="file" 
                                id="cover_image" 
                                name="cover_image" 
                                accept="image/jpeg,image/png,image/webp" 
                                @change="handleImageChange($event)"
                                style="font-size: 12px; width: 100%;"
                            >
                            <span class="cat-hint">Leave empty to preserve existing cover artwork.</span>
                            @error('cover_image') <span class="cat-error">{{ $message }}</span> @enderror
                        </div>

                        @if($book->cover_image)
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #991b1b; cursor: pointer; font-weight: 500;">
                                    <input 
                                        type="checkbox" 
                                        name="remove_cover" 
                                        value="1" 
                                        x-model="removeCover"
                                    >
                                    Remove custom cover (revert to bespoke monogram)
                                </label>
                            </div>
                        @endif

                        <!-- Bespoke Color Palettes -->
                        <div style="margin-top: 16px; border-top: 1px solid #f1f5f9; padding-top: 14px;">
                            <label style="display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 8px;">
                                Typographic Monogram Palettes
                            </label>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px;">
                                @foreach($palettes as $p)
                                    <button 
                                        type="button" 
                                        @click="setPalette('{{ $p['bg'] }}', '{{ $p['fg'] }}')"
                                        style="width: 24px; height: 24px; border-radius: 50%; background: {{ $p['bg'] }}; border: 2px solid white; box-shadow: 0 0 0 1px #ccc; cursor: pointer;"
                                        title="{{ $p['label'] }}"
                                    ></button>
                                @endforeach
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div>
                                    <label style="font-size: 10.5px; color: var(--muted); display: block; margin-bottom: 3px;">Background</label>
                                    <input type="text" name="cover_bg" x-model="coverBg" class="field" style="width: 100%; font-size: 11.5px; padding: 4px 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 10.5px; color: var(--muted); display: block; margin-bottom: 3px;">Text Color</label>
                                    <input type="text" name="cover_fg" x-model="coverFg" class="field" style="width: 100%; font-size: 11.5px; padding: 4px 8px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cataloguing Checklist -->
                    <div class="cat-actions-card">
                        <span style="font-size: 11px; letter-spacing: 1px; text-transform: uppercase; font-weight: 700; color: var(--muted); display: block; margin-bottom: 6px;">
                            Cataloguing Integrity
                        </span>
                        <ul class="cat-checklist">
                            <li class="cat-checklist-item complete">
                                <span>&check;</span>
                                <span>Record ID #{{ $book->id }}</span>
                            </li>
                            <li class="cat-checklist-item" :class="title.trim().length > 0 ? 'complete' : ''">
                                <span x-show="title.trim().length > 0">&check;</span>
                                <span x-show="title.trim().length === 0">&bull;</span>
                                <span>Title specified</span>
                            </li>
                            <li class="cat-checklist-item" :class="author.trim().length > 0 ? 'complete' : ''">
                                <span x-show="author.trim().length > 0">&check;</span>
                                <span x-show="author.trim().length === 0">&bull;</span>
                                <span>Primary author designated</span>
                            </li>
                            <li class="cat-checklist-item complete">
                                <span>&check;</span>
                                <span>{{ $activeFile ? 'Document attached' : 'Mock document active' }}</span>
                            </li>
                            <li class="cat-checklist-item" :class="(hasCoverImage && !removeCover) ? 'complete' : ''">
                                <span x-show="hasCoverImage && !removeCover">&check;</span>
                                <span x-show="!hasCoverImage || removeCover">&bull;</span>
                                <span>{{ $book->cover_image ? 'Custom cover assigned' : 'Palette monogram active' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
