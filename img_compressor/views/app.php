<?php

declare(strict_types=1);

/** @var bool $authenticated */
/** @var string $htmlLocale */
/** @var string $htmlDir */
/** @var \ImgCompressor\Config\AppConfig $config */
/** @var \ImgCompressor\Infrastructure\I18n $i18n */
/** @var \ImgCompressor\Infrastructure\AppPaths $paths */
/** @var string $csrfToken */
/** @var array<string, mixed> $i18nPayload */

$t = static fn(string $key, array $replace = []) => $i18n->get($key, $replace);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLocale, ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars($htmlDir, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t('app.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($paths->assetUrl('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <div id="app">
        <div id="login-screen" class="<?= $authenticated ? 'hidden' : '' ?>">
            <div class="login-card">
                <div class="login-locale">
                    <select id="locale-switcher-login" class="locale-switcher" aria-label="Language">
                        <?php foreach ($i18n->getAvailableLocales() as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $code === $htmlLocale ? 'selected' : '' ?>>
                                <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <h1><?= htmlspecialchars($t('app.title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="subtitle"><?= htmlspecialchars($t('auth.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
                <form id="login-form">
                    <input type="hidden" name="csrf_token" id="csrf-token-input" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <label for="password"><?= htmlspecialchars($t('auth.password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="submit" id="login-btn"><?= htmlspecialchars($t('auth.login'), ENT_QUOTES, 'UTF-8') ?></button>
                    <p id="login-error" class="error hidden"></p>
                </form>
            </div>
        </div>

        <div id="main-screen" class="<?= $authenticated ? '' : 'hidden' ?>">
            <header class="header">
                <h1><?= htmlspecialchars($t('app.title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="header-actions">
                    <select id="locale-switcher" class="locale-switcher" aria-label="Language">
                        <?php foreach ($i18n->getAvailableLocales() as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $code === $htmlLocale ? 'selected' : '' ?>>
                                <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span id="file-count" class="badge"></span>
                    <button id="logout-btn" class="btn-secondary"><?= htmlspecialchars($t('auth.logout'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </header>

            <div id="list-view">
                <div class="pagination-top-bar">
                    <div id="pagination-top" class="pagination pagination-top"></div>
                    <div class="per-page-card">
                        <label for="per-page-input" class="per-page-label">
                            <svg class="per-page-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                <path fill="currentColor" d="M4 6h16v2H4V6zm0 5h10v2H4v-2zm0 5h16v2H4v-2z"/>
                            </svg>
                            <?= htmlspecialchars($t('pagination.per_page'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <div class="per-page-control">
                            <button type="button" id="per-page-dec" class="per-page-step" aria-label="-">−</button>
                            <input type="number" id="per-page-input" class="per-page-input" min="<?= $config->perPageMin() ?>" max="<?= $config->perPageMax() ?>" value="<?= $config->perPage() ?>">
                            <button type="button" id="per-page-inc" class="per-page-step" aria-label="+">+</button>
                            <button type="button" id="per-page-apply" class="per-page-apply"><?= htmlspecialchars($t('pagination.per_page_apply'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </div>
                </div>
                <div class="toolbar">
                    <p><?= htmlspecialchars($t('list.toolbar'), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="legend">
                        <span class="legend-item legend-recent"><?= htmlspecialchars($t('list.legend.recent'), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="legend-item legend-seen"><?= htmlspecialchars($t('list.legend.seen'), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="legend-item legend-done"><?= htmlspecialchars($t('list.legend.done'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="bulk-bar">
                        <label class="select-all-label">
                            <input type="checkbox" id="select-all-page"> <?= htmlspecialchars($t('list.select_all'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <span id="selected-count" class="badge"><?= htmlspecialchars($t('list.selected_count', ['count' => 0]), ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="bulk-quality-wrap">
                            <label for="bulk-quality"><?= htmlspecialchars($t('list.quality'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="number" id="bulk-quality" min="1" max="100" value="70">
                            <span class="bulk-pct">%</span>
                        </div>
                        <button type="button" id="bulk-apply" class="btn-bulk" disabled><?= htmlspecialchars($t('list.bulk_apply'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div id="bulk-progress" class="bulk-progress hidden">
                        <div class="bulk-progress-bar"><div id="bulk-progress-fill"></div></div>
                        <p id="bulk-progress-text"></p>
                    </div>
                </div>
                <div id="file-list" class="file-list"></div>
                <div id="pagination" class="pagination pagination-bottom"></div>
            </div>

            <div id="compress-view" class="hidden">
                <button id="back-btn" class="btn-back"><?= htmlspecialchars($t('compress.back'), ENT_QUOTES, 'UTF-8') ?></button>
                <div class="compress-header">
                    <h2 id="compress-title"></h2>
                    <p id="compress-folder" class="compress-folder"></p>
                    <p id="compress-original-size"></p>
                </div>
                <div id="compress-banner" class="compress-banner hidden" role="status" aria-live="polite">
                    <div id="compress-banner-bar" class="compress-banner-bar hidden">
                        <div id="compress-banner-fill"></div>
                    </div>
                    <p id="compress-banner-text"></p>
                </div>
                <div class="original-preview">
                    <h3><?= htmlspecialchars($t('compress.original_title'), ENT_QUOTES, 'UTF-8') ?> <span class="zoom-hint"><?= htmlspecialchars($t('compress.zoom_hint'), ENT_QUOTES, 'UTF-8') ?></span></h3>
                    <div class="preview-wrap original-wrap">
                        <img id="original-img" alt="original">
                    </div>
                </div>
                <div id="quality-previews" class="quality-grid"></div>
            </div>
        </div>
    </div>

    <div id="lightbox" class="lightbox hidden" aria-hidden="true">
        <div class="lightbox-backdrop"></div>
        <div class="lightbox-panel">
            <div class="lightbox-toolbar">
                <span id="lightbox-title" class="lightbox-title"></span>
                <div class="lightbox-controls">
                    <button type="button" id="lightbox-zoom-out" title="<?= htmlspecialchars($t('lightbox.zoom_out'), ENT_QUOTES, 'UTF-8') ?>">−</button>
                    <span id="lightbox-zoom-level">100%</span>
                    <button type="button" id="lightbox-zoom-in" title="<?= htmlspecialchars($t('lightbox.zoom_in'), ENT_QUOTES, 'UTF-8') ?>">+</button>
                    <button type="button" id="lightbox-zoom-reset" title="<?= htmlspecialchars($t('lightbox.zoom_reset'), ENT_QUOTES, 'UTF-8') ?>">1:1</button>
                    <button type="button" id="lightbox-zoom-fit" title="<?= htmlspecialchars($t('lightbox.zoom_fit'), ENT_QUOTES, 'UTF-8') ?>">Fit</button>
                    <button type="button" id="lightbox-close" title="<?= htmlspecialchars($t('lightbox.close'), ENT_QUOTES, 'UTF-8') ?>">✕</button>
                </div>
            </div>
            <div id="lightbox-viewport" class="lightbox-viewport">
                <img id="lightbox-img" alt="">
            </div>
            <p class="lightbox-hint"><?= htmlspecialchars($t('lightbox.hint'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <?php $pathPayload = $paths->toClientPayload(); ?>
    <script>window.IMG_COMP_PATHS = <?= json_encode($pathPayload, JSON_UNESCAPED_SLASHES) ?>;</script>
    <script>window.IMG_COMP_BASE = <?= json_encode($pathPayload['base'], JSON_UNESCAPED_SLASHES) ?>;</script>
    <script>window.IMG_COMP_API = <?= json_encode($pathPayload['api'], JSON_UNESCAPED_SLASHES) ?>;</script>
    <script>window.IMG_COMP_FILE_BASE = <?= json_encode($pathPayload['files'], JSON_UNESCAPED_SLASHES) ?>;</script>
    <script>window.IMG_COMP_CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>;</script>
    <script>window.I18N = <?= json_encode($i18nPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="<?= htmlspecialchars($paths->assetUrl('assets/js/app.js?v=15'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
