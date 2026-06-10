<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internationalization (BCP 47 / ISO 639-1)
    |--------------------------------------------------------------------------
    | locale: force a language (en|fa), or null for auto-detect via session /
    |         Accept-Language header. Switch at runtime: ?lang=en or ?lang=fa
    */
    'i18n' => [
        'default_locale' => 'en',
        'fallback_locale' => 'en',
        'locale' => 'en', // force en|fa, or null for session / default_locale
        'available_locales' => [
            'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
            'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'dir' => 'rtl'],
        ],
    ],

    // Change this to your browser's User-Agent string
    'allowed_user_agent' => 'allowed_user_agent',

    // Login password — prefer password_hash in config.local.php for production
    // php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
    'password_hash' => null,
    'password' => 'password',

    // Brute-force protection
    'login_max_attempts' => 5,
    'login_lockout_seconds' => 900,

    // Max decoded image size for save API (50 MB)
    'max_upload_bytes' => 52428800,

    // Session lifetime in seconds (8 hours)
    'session_lifetime' => 28800,

    // Minimum file size to show (1 MB)
    'min_file_size' => 1048576,

    // Files per page (default + allowed range for user input)
    'per_page' => 20,
    'per_page_min' => 5,
    'per_page_max' => 100,

    /*
    |--------------------------------------------------------------------------
    | Path resolution (auto-detected by default)
    |--------------------------------------------------------------------------
    | document_root: absolute path to web root (null = use $_SERVER['DOCUMENT_ROOT']
    |                or walk up to find scan_paths)
    | base_path:     URL prefix for this tool, e.g. "/admin/tools/img_compressor/"
    |                (null = derived from SCRIPT_NAME — works in any subdirectory)
    | assets_url_prefix: public URL prefix for scanned images (default "/")
    */
    'document_root' => null,
    'base_path' => null,
    'assets_url_prefix' => '/',

    // Folders to scan (relative to document_root) — each keeps its own path on save
    // Absolute paths are also supported: '/var/www/uploads'
    'scan_paths' => [
        'img/documents',
        'assets',
        // 'uploads/products',
    ],

    // Allowed image extensions
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'],

    // Create backup before overwriting original (true/false)
    'backup_enabled' => true,

    // Backup storage (relative to img_compressor/)
    'backup_dir' => 'assets/backups',

    // Compression quality levels for preview (percent)
    'quality_levels' => [90, 80, 70, 60, 50, 40, 30, 20],
];
