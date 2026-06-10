<?php

declare(strict_types=1);

/**
 * English (en) — ISO 639-1 / BCP 47
 */
return [
    'meta' => [
        'locale' => 'en',
        'direction' => 'ltr',
        'label' => 'English',
    ],
    'strings' => [
        'app.title' => 'Image Compressor',

        'auth.subtitle' => 'Sign in to view files',
        'auth.password' => 'Password',
        'auth.login' => 'Sign in',
        'auth.logout' => 'Sign out',
        'auth.wrong_password' => 'Incorrect password',
        'auth.forbidden' => 'Access denied',

        'list.toolbar' => 'Files over 1 MB — sorted largest to smallest',
        'list.legend.recent' => 'Last visited',
        'list.legend.seen' => 'Visited',
        'list.legend.done' => 'Compressed',
        'list.select_all' => 'Select all',
        'list.selected_count' => ':count selected',
        'list.quality' => 'Quality:',
        'list.bulk_apply' => 'Apply to selected',
        'list.file_count' => ':count files',
        'list.empty' => 'No files over 1 MB found',
        'list.loading' => 'Loading…',
        'list.badge.visited' => 'Visited',

        'pagination.prev' => 'Previous',
        'pagination.next' => 'Next',
        'pagination.page' => 'Page :page of :total',
        'pagination.go' => 'Go',
        'pagination.jump_label' => 'Go to page',
        'pagination.per_page' => 'Per page:',
        'pagination.per_page_apply' => 'Apply',

        'compress.back' => '← Back to list',
        'compress.original_size' => 'Original size: :size',
        'compress.original_title' => 'Original image',
        'compress.zoom_hint' => '(click to zoom)',
        'compress.preview_loading' => 'Generating previews…',
        'compress.preview_progress' => 'Generating preview :current of :total…',
        'compress.preview_done' => 'All previews ready',
        'compress.quality_label' => 'Quality :percent%',
        'compress.savings' => ':size (:percent% smaller)',
        'compress.save' => 'Save to server',
        'compress.download' => 'Download',
        'compress.saving' => 'Saving…',
        'compress.saved' => 'Saved! New size: :size',
        'compress.saved_with_backup' => 'Saved! New size: :size — backup: :backup',
        'compress.lightbox.original' => 'Original — :name (:size)',

        'bulk.confirm' => 'Compress :count file(s) at :quality% quality?',
        'bulk.backup_yes' => 'A backup will be created for each file.',
        'bulk.backup_no' => 'No backup will be created.',
        'bulk.quality_invalid' => 'Quality must be between 1 and 100',
        'bulk.processing' => 'Processing: :name (:current/:total)',
        'bulk.done' => 'Done — success: :done, failed: :failed',

        'lightbox.zoom_out' => 'Zoom out',
        'lightbox.zoom_in' => 'Zoom in',
        'lightbox.zoom_reset' => 'Actual size',
        'lightbox.zoom_fit' => 'Fit to screen',
        'lightbox.close' => 'Close',
        'lightbox.hint' => 'Scroll to zoom — drag to pan — Esc to close',
        'lightbox.quality' => 'Quality :percent% — :size',
        'lightbox.zoom' => 'Zoom',

        'preview.zoom_label' => 'Zoom',

        'error.server' => 'Server error',
        'error.invalid_token' => 'Invalid request token — refresh the page and try again',
        'error.unauthorized' => 'Unauthorized',
        'error.method_not_allowed' => 'Method not allowed',
        'error.incomplete_data' => 'Incomplete data',
        'error.path_forbidden' => 'Path not allowed',
        'error.file_not_found' => 'File not found',
        'error.invalid_image_format' => 'Invalid image format',
        'error.base64_decode' => 'Base64 decode failed',
        'error.backup_dir' => 'Failed to create backup directory',
        'error.backup_create' => 'Failed to create backup',
        'error.save_failed' => 'Failed to save file',
        'error.save' => 'Save failed',
        'error.image_load' => 'Failed to load image',
        'error.compress_failed' => 'Compression failed',
        'error.locale_invalid' => 'Invalid language',
    ],
];
