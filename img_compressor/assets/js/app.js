(function () {
    'use strict';

    function appBasePath() {
        if (window.IMG_COMP_BASE) return window.IMG_COMP_BASE;
        const path = window.location.pathname;
        if (path.endsWith('/')) return path;
        if (path.endsWith('.php')) return path.replace(/\/[^/]+$/, '/') || '/';
        return path + '/';
    }

    function resolveAppUrl(path) {
        const raw = path || 'index.php';
        if (/^https?:\/\//i.test(raw)) return raw;
        if (raw.startsWith('/')) {
            return new URL(raw, window.location.origin).href;
        }
        return new URL(raw, new URL(appBasePath(), window.location.origin)).href;
    }

    let API = resolveAppUrl(window.IMG_COMP_API);
    let fileBase = window.IMG_COMP_FILE_BASE || '/';
    const STORAGE_KEY = 'img_compressor_state';
    let csrfToken = '';

    function getCsrfToken() {
        if (csrfToken) return csrfToken;
        const input = document.getElementById('csrf-token-input');
        if (input?.value) return input.value;
        return window.IMG_COMP_CSRF || '';
    }

    function applyPaths(payload) {
        if (!payload?.paths) return;
        if (payload.paths.api) API = resolveAppUrl(payload.paths.api);
        if (payload.paths.files !== undefined && payload.paths.files !== null) {
            fileBase = payload.paths.files;
        }
    }

    async function parseApiResponse(res) {
        const text = await res.text();
        if (!text) {
            if (!res.ok) {
                throw new Error(`${t('error.server')} (HTTP ${res.status})`);
            }
            return {};
        }

        try {
            return JSON.parse(text);
        } catch {
            throw new Error(`${t('error.server')} (HTTP ${res.status})`);
        }
    }

    function setCsrfToken(token) {
        if (!token) return;
        csrfToken = token;
        const input = document.getElementById('csrf-token-input');
        if (input) input.value = token;
    }

    function appendCsrf(formData) {
        const token = getCsrfToken();
        if (token) formData.set('csrf_token', token);
    }

    setCsrfToken(getCsrfToken());

    function t(key, params = {}) {
        let msg = window.I18N?.messages?.[key] ?? key;
        Object.entries(params).forEach(([name, value]) => {
            msg = msg.split(':' + name).join(String(value));
        });
        return msg;
    }

    function applyDocumentLocale() {
        const i18n = window.I18N || {};
        document.documentElement.lang = i18n.locale || 'en';
        document.documentElement.dir = i18n.dir || 'ltr';
    }

    function currentLocale() {
        return window.I18N?.locale || 'en';
    }

    function mergeI18n(payload) {
        if (!payload?.i18n?.messages) return;
        const pageLocale = currentLocale();
        if (payload.i18n.locale && payload.i18n.locale !== pageLocale) return;
        window.I18N = {
            ...window.I18N,
            ...payload.i18n,
            locale: pageLocale,
            messages: payload.i18n.messages,
        };
        applyDocumentLocale();
    }

    applyDocumentLocale();
    const VISITED_KEY = 'img_compressor_visited';
    const PER_PAGE_KEY = 'img_compressor_per_page';
    let currentPage = 1;
    let perPage = parseInt(localStorage.getItem(PER_PAGE_KEY), 10) || 20;
    let perPageMin = 5;
    let perPageMax = 100;
    let qualityLevels = [90, 80, 70, 60, 50, 40, 30, 20];
    let currentFile = null;
    let compressedBlobs = {};
    let lastVisitedPath = null;
    let selectedFiles = new Map();
    let currentFilesOnPage = [];
    let selectionBatch = false;
    let backupEnabled = true;

    const $ = (sel) => document.querySelector(sel);

    const loginScreen = $('#login-screen');
    const mainScreen = $('#main-screen');
    const loginForm = $('#login-form');
    const loginError = $('#login-error');
    const logoutBtn = $('#logout-btn');
    const fileList = $('#file-list');
    const pagination = $('#pagination');
    const paginationTop = $('#pagination-top');
    const fileCount = $('#file-count');
    const listView = $('#list-view');
    const compressView = $('#compress-view');
    const backBtn = $('#back-btn');
    const originalImg = $('#original-img');
    const compressTitle = $('#compress-title');
    const compressFolder = $('#compress-folder');
    const compressOriginalSize = $('#compress-original-size');
    const qualityPreviews = $('#quality-previews');
    const compressBanner = $('#compress-banner');
    const compressBannerBar = $('#compress-banner-bar');
    const compressBannerFill = $('#compress-banner-fill');
    const compressBannerText = $('#compress-banner-text');
    let compressBannerTimer = null;
    const lightbox = $('#lightbox');
    const lightboxImg = $('#lightbox-img');
    const lightboxTitle = $('#lightbox-title');
    const lightboxViewport = $('#lightbox-viewport');
    const lightboxZoomLevel = $('#lightbox-zoom-level');
    const selectAllPage = $('#select-all-page');
    const selectedCount = $('#selected-count');
    const bulkQuality = $('#bulk-quality');
    const bulkApply = $('#bulk-apply');
    const bulkProgress = $('#bulk-progress');
    const bulkProgressFill = $('#bulk-progress-fill');
    const bulkProgressText = $('#bulk-progress-text');
    const localeSwitcher = $('#locale-switcher');
    const localeSwitcherLogin = $('#locale-switcher-login');
    const perPageInput = $('#per-page-input');
    const perPageApply = $('#per-page-apply');
    const perPageDec = $('#per-page-dec');
    const perPageInc = $('#per-page-inc');

    let lightboxScale = 1;
    let lightboxPanX = 0;
    let lightboxPanY = 0;
    let lightboxDragging = false;
    let lightboxDragStart = { x: 0, y: 0 };
    let lightboxPanStart = { x: 0, y: 0 };

    async function api(action, options = {}) {
        const lang = encodeURIComponent(currentLocale());
        const url = `${API}?action=${action}&lang=${lang}${options.query || ''}`;
        const fetchOpts = {
            method: options.method || 'GET',
            credentials: 'same-origin',
        };
        if (options.body) {
            fetchOpts.method = 'POST';
            if (options.body instanceof FormData) {
                appendCsrf(options.body);
            }
            fetchOpts.body = options.body;
        }
        const token = getCsrfToken();
        if (token) {
            fetchOpts.headers = { ...(fetchOpts.headers || {}), 'X-CSRF-TOKEN': token };
        }
        const res = await fetch(url, fetchOpts);
        const data = await parseApiResponse(res);
        if (data.csrf_token) setCsrfToken(data.csrf_token);
        if (!res.ok) {
            throw new Error(data.error || `${t('error.server')} (HTTP ${res.status})`);
        }
        return data;
    }

    function formatBytes(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }

    function showError(el, msg) {
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function hideError(el) {
        el.classList.add('hidden');
    }

    function loadFileState() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        } catch {
            return {};
        }
    }

    function saveFileState(state) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function loadVisitedList() {
        try {
            return JSON.parse(localStorage.getItem(VISITED_KEY) || '[]');
        } catch {
            return [];
        }
    }

    function saveVisitedList(list) {
        localStorage.setItem(VISITED_KEY, JSON.stringify([...new Set(list)]));
    }

    function isVisited(path) {
        return loadVisitedList().includes(path) || !!getFileState(path)?.visited;
    }

    function markVisited(path) {
        const visited = loadVisitedList();
        if (!visited.includes(path)) {
            visited.push(path);
            saveVisitedList(visited);
        }
        const state = loadFileState();
        state[path] = { ...(state[path] || {}), visited: true, visitedAt: Date.now() };
        saveFileState(state);
    }

    function migrateVisitedPath(oldPath, newPath) {
        const visited = loadVisitedList();
        const idx = visited.indexOf(oldPath);
        if (idx !== -1) visited[idx] = newPath;
        else if (!visited.includes(newPath)) visited.push(newPath);
        saveVisitedList(visited);
    }

    function markCompressed(path, quality, newPath) {
        const state = loadFileState();
        const finalPath = newPath || path;

        if (newPath && newPath !== path) {
            migrateVisitedPath(path, newPath);
            if (state[path]) {
                state[newPath] = { ...state[path], ...state[newPath] };
                delete state[path];
            }
        }

        state[finalPath] = {
            ...(state[finalPath] || {}),
            visited: true,
            compressed: true,
            quality,
            compressedAt: Date.now(),
        };
        markVisited(finalPath);
        saveFileState(state);
    }

    function getFileState(path) {
        const state = loadFileState();
        return state[path] || null;
    }

    function updateSelectionUI() {
        if (!selectedCount || !bulkApply || !selectAllPage) return;

        const count = selectedFiles.size;
        selectedCount.textContent = t('list.selected_count', { count });
        bulkApply.disabled = count === 0;

        if (!selectionBatch && currentFilesOnPage.length) {
            const allSelected = currentFilesOnPage.every((f) => selectedFiles.has(f.path));
            selectAllPage.checked = allSelected;
            selectAllPage.indeterminate = count > 0 && !allSelected;
        } else if (!selectionBatch) {
            selectAllPage.checked = false;
            selectAllPage.indeterminate = false;
        }
    }

    function setItemSelected(path, checked) {
        const item = fileList.querySelector(`.file-item[data-path="${CSS.escape(path)}"]`);
        if (item) {
            item.classList.toggle('is-selected', checked);
            const cb = item.querySelector('.file-checkbox');
            if (cb) cb.checked = checked;
        }
    }

    function toggleSelection(file, checked, skipUi) {
        if (checked) selectedFiles.set(file.path, file);
        else selectedFiles.delete(file.path);

        setItemSelected(file.path, checked);

        if (!skipUi) updateSelectionUI();
    }

    function selectAllOnPage(checked) {
        selectionBatch = true;
        currentFilesOnPage.forEach((f) => toggleSelection(f, checked, true));
        selectionBatch = false;
        if (selectAllPage) selectAllPage.checked = checked;
        updateSelectionUI();
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError(loginError);
        const formData = new FormData(loginForm);
        appendCsrf(formData);
        try {
            await api('login', { method: 'POST', body: formData });
            loginScreen.classList.add('hidden');
            mainScreen.classList.remove('hidden');
            loadFiles(1);
        } catch (err) {
            showError(loginError, err.message);
        }
    });

    logoutBtn.addEventListener('click', async () => {
        await api('logout');
        mainScreen.classList.add('hidden');
        loginScreen.classList.remove('hidden');
        $('#password').value = '';
    });

    backBtn.addEventListener('click', () => {
        if (!lightbox.classList.contains('hidden')) closeLightbox();
        compressView.classList.add('hidden');
        listView.classList.remove('hidden');
        hideCompressBanner();
        compressedBlobs = {};
        qualityPreviews.innerHTML = '';
        loadFiles(currentPage);
    });

    function showCompressBanner(type, text, progress = null) {
        if (!compressBanner || !compressBannerText) return;
        clearTimeout(compressBannerTimer);
        compressBanner.className = `compress-banner compress-banner-${type}`;
        compressBannerText.textContent = text;
        if (compressBannerBar && compressBannerFill) {
            if (progress !== null) {
                compressBannerBar.classList.remove('hidden');
                compressBannerFill.style.width = `${Math.min(100, Math.max(0, progress))}%`;
            } else {
                compressBannerBar.classList.add('hidden');
            }
        }
        compressBanner.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideCompressBanner(delay = 0) {
        if (!compressBanner) return;
        clearTimeout(compressBannerTimer);
        if (delay > 0) {
            compressBannerTimer = setTimeout(() => compressBanner.classList.add('hidden'), delay);
        } else {
            compressBanner.classList.add('hidden');
        }
    }

    if (selectAllPage) {
        selectAllPage.addEventListener('change', () => {
            selectAllOnPage(selectAllPage.checked);
        });
    }

    if (bulkApply) {
        bulkApply.addEventListener('click', (e) => {
            e.preventDefault();
            bulkCompressSelected();
        });
    }

    if (bulkQuality) {
        bulkQuality.addEventListener('input', () => {
            const v = parseInt(bulkQuality.value, 10);
            if (v < 1) bulkQuality.value = 1;
            if (v > 100) bulkQuality.value = 100;
        });
    }

    function bindLocaleSwitcher(select) {
        if (!select) return;
        select.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', select.value);
            window.location.href = url.toString();
        });
    }

    bindLocaleSwitcher(localeSwitcher);
    bindLocaleSwitcher(localeSwitcherLogin);

    function clampPerPage(value) {
        const n = parseInt(value, 10);
        if (!n || n < perPageMin) return perPageMin;
        if (n > perPageMax) return perPageMax;
        return n;
    }

    function syncPerPageInput() {
        if (perPageInput) {
            perPageInput.min = String(perPageMin);
            perPageInput.max = String(perPageMax);
            perPageInput.value = String(perPage);
        }
    }

    function applyPerPage() {
        if (!perPageInput) return;
        perPage = clampPerPage(perPageInput.value);
        localStorage.setItem(PER_PAGE_KEY, String(perPage));
        syncPerPageInput();
        loadFiles(1);
    }

    function stepPerPage(delta) {
        if (!perPageInput) return;
        perPageInput.value = String(clampPerPage(parseInt(perPageInput.value, 10) + delta));
    }

    if (perPageApply) perPageApply.addEventListener('click', applyPerPage);
    if (perPageDec) perPageDec.addEventListener('click', () => stepPerPage(-5));
    if (perPageInc) perPageInc.addEventListener('click', () => stepPerPage(5));
    if (perPageInput) {
        perPageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyPerPage();
            }
        });
        syncPerPageInput();
    }

    async function loadFiles(page) {
        currentPage = page;
        fileList.innerHTML = `<div class="loading-spinner">${escapeHtml(t('list.loading'))}</div>`;
        try {
            const data = await api('files', { query: `&page=${page}&per_page=${perPage}` });
            qualityLevels = data.quality_levels || qualityLevels;
            if (typeof data.backup_enabled === 'boolean') backupEnabled = data.backup_enabled;
            if (typeof data.per_page_min === 'number') perPageMin = data.per_page_min;
            if (typeof data.per_page_max === 'number') perPageMax = data.per_page_max;
            if (data.pagination?.per_page) perPage = data.pagination.per_page;
            localStorage.setItem(PER_PAGE_KEY, String(perPage));
            syncPerPageInput();
            applyPaths(data);
            mergeI18n(data);
            renderFiles(data.files);
            renderPagination(data.pagination);
            fileCount.textContent = t('list.file_count', { count: data.pagination.total });
        } catch (err) {
            fileList.innerHTML = `<div class="empty-state">${err.message}</div>`;
        }
    }

    function renderFiles(files) {
        currentFilesOnPage = files;

        if (!files.length) {
            fileList.innerHTML = `<div class="empty-state">${escapeHtml(t('list.empty'))}</div>`;
            updateSelectionUI();
            return;
        }

        fileList.innerHTML = files.map((f) => {
            const state = getFileState(f.path);
            const visited = isVisited(f.path);
            const classes = ['file-item'];
            if (visited) classes.push('file-item-seen');
            if (lastVisitedPath === f.path) classes.push('file-item-recent');
            if (state?.compressed) classes.push('file-item-done');
            if (selectedFiles.has(f.path)) classes.push('is-selected');

            let badge = '';
            if (state?.compressed) {
                badge = `<span class="file-status-badge badge-done">Q${state.quality}%</span>`;
            } else if (visited) {
                badge = `<span class="file-status-badge badge-seen">${escapeHtml(t('list.badge.visited'))}</span>`;
            }

            const fileJson = escapeAttr(JSON.stringify(f));

            return `
            <div class="${classes.join(' ')}" data-path="${escapeAttr(f.path)}" data-url="${escapeAttr(f.url)}" data-name="${escapeAttr(f.name)}" data-size="${f.size}" data-file="${fileJson}">
                <input type="checkbox" class="file-checkbox" ${selectedFiles.has(f.path) ? 'checked' : ''}>
                <img class="file-thumb" src="${escapeAttr(f.url)}" alt="" loading="lazy">
                <div class="file-info">
                    ${f.folder ? `<div class="file-folder">${escapeHtml(f.folder)}/</div>` : ''}
                    <div class="file-name">${escapeHtml(f.name)}</div>
                    <div class="file-meta">
                        <span>${f.modified_human}</span>
                    </div>
                </div>
                ${badge}
                <div class="file-size">${escapeHtml(f.size_human)}</div>
            </div>`;
        }).join('');

        fileList.querySelectorAll('.file-item').forEach((item) => {
            const file = parseFileFromItem(item);
            const cb = item.querySelector('.file-checkbox');

            cb.addEventListener('click', (e) => e.stopPropagation());
            cb.addEventListener('mousedown', (e) => e.stopPropagation());
            cb.addEventListener('change', (e) => {
                e.stopPropagation();
                toggleSelection(file, e.target.checked);
            });

            item.addEventListener('click', (e) => {
                if (e.target.closest('.file-checkbox')) return;
                openCompress(item.dataset);
            });
        });

        updateSelectionUI();
    }

    function buildPaginationHtml(p) {
        let html = `<button ${p.page <= 1 ? 'disabled' : ''} data-page="${p.page - 1}">${escapeHtml(t('pagination.prev'))}</button>`;
        const start = Math.max(1, p.page - 2);
        const end = Math.min(p.total_pages, p.page + 2);
        for (let i = start; i <= end; i++) {
            html += `<button class="${i === p.page ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        html += `<button ${p.page >= p.total_pages ? 'disabled' : ''} data-page="${p.page + 1}">${escapeHtml(t('pagination.next'))}</button>`;
        html += `<span class="page-info">${escapeHtml(t('pagination.page', { page: p.page, total: p.total_pages }))}</span>`;
        html += `<span class="page-jump">
            <label class="page-jump-label">${escapeHtml(t('pagination.jump_label'))}</label>
            <input type="number" class="page-jump-input" min="1" max="${p.total_pages}" value="${p.page}" aria-label="${escapeAttr(t('pagination.jump_label'))}">
            <button type="button" class="page-jump-btn">${escapeHtml(t('pagination.go'))}</button>
        </span>`;
        return html;
    }

    function bindPagination(container, p) {
        container.querySelectorAll('button[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => loadFiles(parseInt(btn.dataset.page, 10)));
        });

        const input = container.querySelector('.page-jump-input');
        const goBtn = container.querySelector('.page-jump-btn');
        if (!input || !goBtn) return;

        const jumpToPage = () => {
            const page = parseInt(input.value, 10);
            if (page >= 1 && page <= p.total_pages) {
                loadFiles(page);
            } else {
                input.value = String(p.page);
            }
        };

        goBtn.addEventListener('click', jumpToPage);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                jumpToPage();
            }
        });
    }

    function renderPagination(p) {
        const containers = [paginationTop, pagination].filter(Boolean);
        const html = p.total_pages <= 1 ? '' : buildPaginationHtml(p);
        containers.forEach((el) => {
            el.innerHTML = html;
            if (html) bindPagination(el, p);
        });
    }

    function buildFileUrlFromPath(path) {
        const clean = path.replace(/^\/+/, '');
        const prefix = fileBase === '/' ? '' : String(fileBase).replace(/\/+$/, '');
        return prefix === '' ? '/' + clean : prefix + '/' + clean;
    }

    async function openCompress(dataset) {
        currentFile = {
            path: dataset.path,
            url: dataset.url,
            name: dataset.name,
            size: parseInt(dataset.size, 10),
        };
        compressedBlobs = {};
        lastVisitedPath = currentFile.path;
        markVisited(currentFile.path);

        listView.classList.add('hidden');
        compressView.classList.remove('hidden');
        hideCompressBanner();

        compressTitle.textContent = currentFile.name;
        if (compressFolder) {
            compressFolder.textContent = currentFile.path;
        }
        compressOriginalSize.textContent = t('compress.original_size', { size: formatBytes(currentFile.size) });
        originalImg.src = currentFile.url;
        originalImg.onclick = () => openLightbox(
            currentFile.url,
            t('compress.lightbox.original', { name: currentFile.name, size: formatBytes(currentFile.size) })
        );

        qualityPreviews.innerHTML = '';
        showCompressBanner('loading', t('compress.preview_loading'), 5);

        try {
            const img = await loadImage(currentFile.url);
            const total = qualityLevels.length;
            for (let i = 0; i < total; i++) {
                const q = qualityLevels[i];
                showCompressBanner(
                    'loading',
                    t('compress.preview_progress', { current: i + 1, total }),
                    Math.round((i / total) * 100)
                );
                await renderQualityPreview(img, q);
            }
            showCompressBanner('success', t('compress.preview_done'), 100);
            hideCompressBanner(3500);
        } catch (err) {
            showCompressBanner('error', `${t('error.image_load')}: ${err.message}`);
            qualityPreviews.innerHTML = `<div class="empty-state">${escapeHtml(t('error.image_load'))}: ${escapeHtml(err.message)}</div>`;
        }
    }

    function parseFileFromItem(item) {
        try {
            return JSON.parse(item.dataset.file);
        } catch {
            return {
                path: item.dataset.path,
                url: item.dataset.url,
                name: item.dataset.name,
                size: parseInt(item.dataset.size, 10),
            };
        }
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error(t('error.image_load')));
            img.src = src.includes('?') ? src : `${src}?t=${Date.now()}`;
        });
    }

    async function renderQualityPreview(img, quality) {
        const blob = await compressImage(img, quality / 100);
        compressedBlobs[quality] = blob;

        const card = document.createElement('div');
        card.className = 'quality-card';
        card.dataset.quality = quality;

        const previewUrl = URL.createObjectURL(blob);
        const savings = ((1 - blob.size / currentFile.size) * 100).toFixed(1);

        card.innerHTML = `
            <div class="quality-card-header">
                <span class="quality-label">${escapeHtml(t('compress.quality_label', { percent: quality }))}</span>
                <span class="quality-size">${escapeHtml(t('compress.savings', { size: formatBytes(blob.size), percent: savings }))}</span>
            </div>
            <div class="preview-wrap" title="${escapeAttr(t('preview.zoom_label'))}">
                <img src="${previewUrl}" alt="quality ${quality}">
            </div>
            <div class="quality-card-actions">
                <button class="btn-save" data-quality="${quality}">${escapeHtml(t('compress.save'))}</button>
                <button class="btn-download" data-quality="${quality}">${escapeHtml(t('compress.download'))}</button>
            </div>
        `;

        qualityPreviews.appendChild(card);

        card.querySelector('.preview-wrap img').addEventListener('click', () => {
            openLightbox(previewUrl, t('lightbox.quality', { percent: quality, size: formatBytes(blob.size) }));
        });
        card.querySelector('.btn-save').addEventListener('click', (e) => {
            e.stopPropagation();
            saveCompressed(quality, card);
        });
        card.querySelector('.btn-download').addEventListener('click', (e) => {
            e.stopPropagation();
            downloadCompressed(quality);
        });
    }

    function compressImage(img, quality) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            canvas.toBlob(
                (blob) => {
                    if (!blob) reject(new Error(t('error.compress_failed')));
                    else resolve(blob);
                },
                'image/jpeg',
                quality
            );
        });
    }

    function blobToDataUrl(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    async function saveBlobToServer(path, blob) {
        const dataUrl = await blobToDataUrl(blob);
        const formData = new FormData();
        formData.append('path', path);
        formData.append('image', dataUrl);
        appendCsrf(formData);

        const saveToken = getCsrfToken();
        const saveHeaders = saveToken ? { 'X-CSRF-TOKEN': saveToken } : {};
        const res = await fetch(`${API}?action=save&lang=${encodeURIComponent(currentLocale())}`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: saveHeaders,
        });
        const data = await parseApiResponse(res);
        if (data.csrf_token) setCsrfToken(data.csrf_token);
        if (!res.ok) {
            throw new Error(data.error || `${t('error.save')} (HTTP ${res.status})`);
        }
        return data;
    }

    async function saveCompressed(quality, card) {
        const blob = compressedBlobs[quality];
        if (!blob) return;

        showCompressBanner('info', t('compress.saving'), null);

        try {
            const data = await saveBlobToServer(currentFile.path, blob);
            markCompressed(currentFile.path, quality, data.path);
            if (data.path !== currentFile.path) {
                currentFile.path = data.path;
                currentFile.url = buildFileUrlFromPath(data.path);
                if (compressFolder) compressFolder.textContent = data.path;
                lastVisitedPath = data.path;
            }

            showCompressBanner(
                'success',
                data.backup
                    ? t('compress.saved_with_backup', { size: data.new_size_human, backup: data.backup })
                    : t('compress.saved', { size: data.new_size_human }),
                null
            );

            document.querySelectorAll('.quality-card').forEach((c) => c.classList.remove('selected'));
            card.classList.add('selected');
        } catch (err) {
            showCompressBanner('error', err.message, null);
        }
    }

    async function bulkCompressSelected() {
        const quality = parseInt(bulkQuality?.value, 10);
        if (!quality || quality < 1 || quality > 100) {
            alert(t('bulk.quality_invalid'));
            return;
        }

        const files = [...selectedFiles.values()];
        if (!files.length) return;

        const backupNote = backupEnabled ? '\n' + t('bulk.backup_yes') : '\n' + t('bulk.backup_no');
        if (!confirm(t('bulk.confirm', { count: files.length, quality }) + backupNote)) {
            return;
        }

        bulkApply.disabled = true;
        bulkProgress.classList.remove('hidden');
        let done = 0;
        let failed = 0;
        const errors = [];

        for (const file of files) {
            const label = file.name || file.path;
            bulkProgressText.textContent = t('bulk.processing', {
                name: label,
                current: done + failed + 1,
                total: files.length,
            });
            bulkProgressFill.style.width = `${((done + failed) / files.length) * 100}%`;

            try {
                const img = await loadImage(file.url);
                const blob = await compressImage(img, quality / 100);
                const data = await saveBlobToServer(file.path, blob);
                markCompressed(file.path, quality, data.path);
                selectedFiles.delete(file.path);
                if (data.path !== file.path) {
                    selectedFiles.set(data.path, { ...file, path: data.path, url: buildFileUrlFromPath(data.path) });
                }
                markVisited(data.path);
                done++;
            } catch (err) {
                failed++;
                errors.push(`${label}: ${err.message}`);
            }
        }

        bulkProgressFill.style.width = '100%';
        bulkProgressText.textContent = t('bulk.done', { done, failed });
        if (errors.length) {
            bulkProgressText.textContent += ` — ${errors.slice(0, 2).join(' | ')}`;
        }
        bulkApply.disabled = selectedFiles.size === 0;
        updateSelectionUI();
        setTimeout(() => bulkProgress.classList.add('hidden'), 6000);
        loadFiles(currentPage);
    }

    function downloadCompressed(quality) {
        const blob = compressedBlobs[quality];
        if (!blob) return;
        const ext = currentFile.name.replace(/\.[^.]+$/, '');
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `${ext}_q${quality}.jpg`;
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/"/g, '&quot;');
    }

    function applyLightboxTransform() {
        lightboxImg.style.transform = `translate(calc(-50% + ${lightboxPanX}px), calc(-50% + ${lightboxPanY}px)) scale(${lightboxScale})`;
        lightboxZoomLevel.textContent = Math.round(lightboxScale * 100) + '%';
    }

    function resetLightboxView() {
        lightboxScale = 1;
        lightboxPanX = 0;
        lightboxPanY = 0;
        applyLightboxTransform();
    }

    function fitLightboxImage() {
        const vw = lightboxViewport.clientWidth;
        const vh = lightboxViewport.clientHeight;
        const iw = lightboxImg.naturalWidth;
        const ih = lightboxImg.naturalHeight;
        if (!iw || !ih) return;
        lightboxScale = Math.min(vw / iw, vh / ih, 1);
        lightboxPanX = 0;
        lightboxPanY = 0;
        applyLightboxTransform();
    }

    function openLightbox(src, title) {
        lightboxTitle.textContent = title;
        lightboxImg.onload = () => fitLightboxImage();
        lightboxImg.src = src;
        lightbox.classList.remove('hidden');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.add('hidden');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImg.src = '';
        document.body.style.overflow = '';
        resetLightboxView();
    }

    function zoomLightbox(delta) {
        const next = Math.min(8, Math.max(0.1, lightboxScale + delta));
        lightboxScale = next;
        applyLightboxTransform();
    }

    $('#lightbox-close').addEventListener('click', closeLightbox);
    $('.lightbox-backdrop').addEventListener('click', closeLightbox);
    $('#lightbox-zoom-in').addEventListener('click', () => zoomLightbox(0.25));
    $('#lightbox-zoom-out').addEventListener('click', () => zoomLightbox(-0.25));
    $('#lightbox-zoom-reset').addEventListener('click', () => {
        lightboxScale = 1;
        lightboxPanX = 0;
        lightboxPanY = 0;
        applyLightboxTransform();
    });
    $('#lightbox-zoom-fit').addEventListener('click', fitLightboxImage);

    lightboxViewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY < 0 ? 0.15 : -0.15;
        zoomLightbox(delta);
    }, { passive: false });

    lightboxViewport.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        lightboxDragging = true;
        lightboxViewport.classList.add('dragging');
        lightboxDragStart = { x: e.clientX, y: e.clientY };
        lightboxPanStart = { x: lightboxPanX, y: lightboxPanY };
    });

    window.addEventListener('mousemove', (e) => {
        if (!lightboxDragging) return;
        lightboxPanX = lightboxPanStart.x + (e.clientX - lightboxDragStart.x);
        lightboxPanY = lightboxPanStart.y + (e.clientY - lightboxDragStart.y);
        applyLightboxTransform();
    });

    window.addEventListener('mouseup', () => {
        lightboxDragging = false;
        lightboxViewport.classList.remove('dragging');
    });

    document.addEventListener('keydown', (e) => {
        if (lightbox.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === '+' || e.key === '=') zoomLightbox(0.25);
        if (e.key === '-') zoomLightbox(-0.25);
    });

    function syncVisitedFromState() {
        const state = loadFileState();
        const visited = loadVisitedList();
        let changed = false;
        Object.entries(state).forEach(([path, s]) => {
            if (s?.visited && !visited.includes(path)) {
                visited.push(path);
                changed = true;
            }
        });
        if (changed) saveVisitedList(visited);
    }

    syncVisitedFromState();

    if (!mainScreen.classList.contains('hidden')) {
        loadFiles(1);
    }
})();
