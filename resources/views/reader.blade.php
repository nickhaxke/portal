<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reading: {{ $book->title }} — Executive E-Library</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $pdfWorkerPath = public_path('vendor/pdfjs/pdf.worker.min.js');
        $pdfWorkerVer = file_exists($pdfWorkerPath) ? filemtime($pdfWorkerPath) : '4.0.379';
        $pdfMinPath = public_path('vendor/pdfjs/pdf.min.js');
        $pdfMinVer = file_exists($pdfMinPath) ? filemtime($pdfMinPath) : '4.0.379';
    @endphp
    <link rel="preload" href="{{ asset('vendor/pdfjs/pdf.worker.min.js') }}?v={{ $pdfWorkerVer }}" as="script">
    <script src="{{ asset('vendor/pdfjs/pdf.min.js') }}?v={{ $pdfMinVer }}"></script>
    <style>
        body {
            background-color: #f5f4ef;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .reader-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .reader-stage {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 30px 15px;
            overflow-y: auto;
        }
        .canvas-card {
            background: #ffffff;
            box-shadow: 0 10px 35px rgba(16, 41, 70, 0.12);
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2ded5;
            position: relative;
        }
        #pdf-canvas {
            display: block;
        }
        .reader-bottom-bar {
            background: #ffffff;
            border-top: 1px solid var(--border);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            bottom: 0;
            z-index: 90;
        }
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--navy);
            z-index: 10;
        }
        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #e0e5eb;
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 12px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="reader-body">

    <!-- Reader Navbar -->
    <header class="reader-nav">
        <a href="{{ route('books.show', $book->slug) }}" class="plain" style="gap: 8px;">
            &larr; <span style="font-weight: 600;">Back to Details</span>
        </a>

        <div style="text-align: center; max-width: 450px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            <strong style="font-family: 'Playfair Display', serif; font-size: 16px; color: var(--navy);">{{ $book->title }}</strong>
            <small style="display: block; color: var(--muted); font-size: 12px;">By {{ $book->author_name }}</small>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <button aria-label="Zoom out" id="zoom-out" class="outline" style="padding: 6px 12px; font-size: 13px;" title="Zoom Out">&minus;</button>
            <span id="zoom-level" style="font-size: 12px; font-weight: 600; min-width: 45px; text-align: center;">100%</span>
            <button aria-label="Zoom in" id="zoom-in" class="outline" style="padding: 6px 12px; font-size: 13px;" title="Zoom In">&plus;</button>
        </div>
    </header>

    <!-- Reading Stage -->
    <main class="reader-stage">
        <div class="canvas-card" id="canvas-card">
            <div class="loading-overlay" id="loading-spinner">
                <div class="spinner"></div>
                <span>Rendering Executive Document...</span>
            </div>
            <canvas id="pdf-canvas"></canvas>
        </div>
    </main>

    <!-- Reader Bottom Controls -->
    <footer class="reader-bottom-bar">
        <div style="display: flex; gap: 10px;">
            <button id="prev-page" class="outline" style="padding: 8px 18px; font-size: 13px;">&larr; Previous</button>
            <button id="next-page" class="gold" style="padding: 8px 18px; font-size: 13px;">Next &rarr;</button>
        </div>

        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="font-size: 14px; font-weight: 600; color: var(--navy);">
                Page <span id="current-page">{{ $initialPage ?? 1 }}</span> of <span id="total-pages">--</span>
            </div>

            <!-- Bookmark Button -->
            <button id="btn-bookmark" class="outline" style="padding: 5px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;" title="Bookmark this page">
                <x-icon name="bookmark" size="13" /> Bookmark
            </button>

            @if(isset($bookmarks) && $bookmarks->isNotEmpty())
                <select aria-label="Jump to saved bookmark" id="jump-bookmark" style="padding: 5px 8px; font-size: 12px; border: 1px solid #d5dce5; border-radius: 4px; background: #fff; color: var(--navy); cursor: pointer;">
                    <option value="">Saved Bookmarks ({{ $bookmarks->count() }})...</option>
                    @foreach($bookmarks as $bm)
                        <option value="{{ $bm->page_number }}">Page {{ $bm->page_number }} — {{ $bm->title ?: 'Page '.$bm->page_number }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div>
            @if(!empty($canDownload) && $canDownload)
                <a href="{{ $streamUrl }}" target="_blank" class="plain" style="font-size: 13px; color: var(--gold); font-weight: 600;" title="Download authorized copy">
                    Download Document ↗
                </a>
            @else
                <span style="font-size: 12px; color: var(--muted); display: inline-flex; align-items: center; gap: 5px;" title="This publication is configured for online reading only.">
                    <x-icon name="lock" size="13" /> Online Reading Only
                </span>
            @endif
        </div>
    </footer>

    <!-- Toast Notification -->
    <div id="reader-toast" style="display: none; position: fixed; top: 80px; right: 25px; z-index: 1000; background: var(--navy); color: #fff; padding: 10px 18px; border-radius: 6px; font-size: 13px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); animation: fadeIn 0.3s;">
    </div>

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}?v={{ $pdfWorkerVer }}";

        const url = "{!! $streamUrl !!}";
        const fileSize = {{ $pdfFile?->file_size ?? 0 }};
        let pdfDoc = null;
        let pageNum = {{ $initialPage ?? 1 }};
        let pageRendering = false;
        let pageNumPending = null;
        let currentRenderTask = null;
        let scale = 1.35;
        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');
        const spinner = document.getElementById('loading-spinner');
        let beaconTimer = null;

        function showToast(message) {
            const toast = document.getElementById('reader-toast');
            if (toast) {
                toast.textContent = message;
                toast.style.display = 'block';
                setTimeout(() => { toast.style.display = 'none'; }, 3000);
            }
        }

        @if(isset($initialPage) && $initialPage > 1)
            showToast("Resumed from your saved progress (Page {{ $initialPage }})");
        @endif

        // Adjust scale for smaller screens
        if (window.innerWidth < 800) {
            scale = 0.85;
        } else if (window.innerWidth < 1200) {
            scale = 1.1;
        }

        function sendProgressBeacon(page, total) {
            clearTimeout(beaconTimer);
            beaconTimer = setTimeout(() => {
                fetch("{{ route('books.progress', $book->slug) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ current_page: page, total_pages: total })
                }).catch(() => {});
            }, 800);
        }

        function renderPage(num) {
            pageRendering = true;
            spinner.style.display = 'flex';

            if (currentRenderTask) {
                currentRenderTask.cancel();
                currentRenderTask = null;
            }

            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                currentRenderTask = page.render(renderContext);

                currentRenderTask.promise.then(function() {
                    pageRendering = false;
                    currentRenderTask = null;
                    spinner.style.display = 'none';
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                    if (pdfDoc) {
                        sendProgressBeacon(num, pdfDoc.numPages);
                    }
                }).catch(function(err) {
                    if (err && err.name === 'RenderingCancelledException') {
                        return;
                    }
                    pageRendering = false;
                    currentRenderTask = null;
                    spinner.style.display = 'none';
                });
            });

            document.getElementById('current-page').textContent = num;
            document.getElementById('zoom-level').textContent = Math.round(scale * 100 / 1.35) + '%';
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function onPrevPage() {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        }
        document.getElementById('prev-page').addEventListener('click', onPrevPage);

        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        }
        document.getElementById('next-page').addEventListener('click', onNextPage);

        // Bookmark controls
        const bookmarkBtn = document.getElementById('btn-bookmark');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', function() {
                fetch("{{ route('books.bookmark', $book->slug) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ page_number: pageNum })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Bookmarked Page ${pageNum}`);
                    } else {
                        showToast('Sign in to save permanent bookmarks');
                    }
                })
                .catch(() => showToast('Bookmark saved'));
            });
        }

        const jumpBookmarkSelect = document.getElementById('jump-bookmark');
        if (jumpBookmarkSelect) {
            jumpBookmarkSelect.addEventListener('change', function() {
                const targetPage = parseInt(this.value, 10);
                if (targetPage && targetPage >= 1 && pdfDoc && targetPage <= pdfDoc.numPages) {
                    pageNum = targetPage;
                    queueRenderPage(pageNum);
                }
            });
        }

        document.getElementById('zoom-in').addEventListener('click', function() {
            scale = Math.min(3.0, scale + 0.2);
            queueRenderPage(pageNum);
        });

        document.getElementById('zoom-out').addEventListener('click', function() {
            scale = Math.max(0.6, scale - 0.2);
            queueRenderPage(pageNum);
        });

        // Keyboard navigation
        window.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' || e.key === 'PageDown') {
                onNextPage();
            } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                onPrevPage();
            }
        });

        // Adaptive PDF Loading:
        // For documents <= 8MB, progressive streaming (disableAutoFetch: false) avoids 3-4 sequential round-trip latency stalls
        // on production WAN connections. For large archives (> 8MB), chunked range requests conserve memory.
        const isSmallDocument = fileSize > 0 && fileSize <= (8 * 1024 * 1024);
        const docOptions = {
            url: url,
            withCredentials: true,
            disableStream: false,
            disableAutoFetch: !isSmallDocument,
            rangeChunkSize: isSmallDocument ? 2097152 : 1048576,
        };

        const loadingTask = pdfjsLib.getDocument(docOptions);

        loadingTask.onProgress = function(progressData) {
            if (progressData && progressData.total > 0 && spinner) {
                const percent = Math.min(100, Math.round((progressData.loaded / progressData.total) * 100));
                const statusSpan = spinner.querySelector('span');
                if (statusSpan) {
                    statusSpan.textContent = `Streaming Executive Document (${percent}%)...`;
                }
            }
        };

        loadingTask.promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            document.getElementById('total-pages').textContent = pdfDoc.numPages;
            renderPage(pageNum);
        }).catch(function(error) {
            spinner.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <div style="color: #c53030; font-weight: 600; margin-bottom: 10px;">Failed to load PDF document.</div>
                    <div style="font-size: 12px; color: var(--muted); margin-bottom: 14px;">The connection timed out or the authorization session expired.</div>
                    <button onclick="window.location.reload()" class="gold" style="padding: 6px 14px; font-size: 12px; cursor: pointer;">Retry Loading</button>
                </div>
            `;
            console.error('Error loading PDF:', error);
        });
    </script>
</body>
</html>
