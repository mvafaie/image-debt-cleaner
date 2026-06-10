<?php

declare(strict_types=1);

/**
 * Persian / Farsi (fa) — ISO 639-1 / BCP 47
 */
return [
    'meta' => [
        'locale' => 'fa',
        'direction' => 'rtl',
        'label' => 'فارسی',
    ],
    'strings' => [
        'app.title' => 'فشرده‌ساز تصویر',

        'auth.subtitle' => 'ورود برای مشاهده فایل‌ها',
        'auth.password' => 'رمز عبور',
        'auth.login' => 'ورود',
        'auth.logout' => 'خروج',
        'auth.wrong_password' => 'رمز عبور اشتباه است',
        'auth.forbidden' => 'دسترسی مجاز نیست',

        'list.toolbar' => 'فایل‌های بالای ۱ مگابایت — مرتب‌شده از بزرگ به کوچک',
        'list.legend.recent' => 'آخرین بازدید',
        'list.legend.seen' => 'بازدید شده',
        'list.legend.done' => 'فشرده شده',
        'list.select_all' => 'انتخاب همه',
        'list.selected_count' => ':count انتخاب',
        'list.quality' => 'کیفیت:',
        'list.bulk_apply' => 'اعمال روی انتخاب‌شده‌ها',
        'list.file_count' => ':count فایل',
        'list.empty' => 'فایلی بالای ۱ مگابایت یافت نشد',
        'list.loading' => 'در حال بارگذاری…',
        'list.badge.visited' => 'بازدید',

        'pagination.prev' => 'قبلی',
        'pagination.next' => 'بعدی',
        'pagination.page' => 'صفحه :page از :total',
        'pagination.go' => 'برو',
        'pagination.jump_label' => 'برو به صفحه',
        'pagination.per_page' => 'تعداد در صفحه:',
        'pagination.per_page_apply' => 'اعمال',

        'compress.back' => '← بازگشت به لیست',
        'compress.original_size' => 'حجم اصلی: :size',
        'compress.original_title' => 'تصویر اصلی',
        'compress.zoom_hint' => '(کلیک برای بزرگ‌نمایی)',
        'compress.preview_loading' => 'در حال ساخت پیش‌نمایش‌ها…',
        'compress.preview_progress' => 'ساخت پیش‌نمایش :current از :total…',
        'compress.preview_done' => 'همه پیش‌نمایش‌ها آماده شد',
        'compress.quality_label' => 'کیفیت :percent%',
        'compress.savings' => ':size (:percent% کمتر)',
        'compress.save' => 'ذخیره روی سرور',
        'compress.download' => 'دانلود',
        'compress.saving' => 'در حال ذخیره…',
        'compress.saved' => 'ذخیره شد! حجم جدید: :size',
        'compress.saved_with_backup' => 'ذخیره شد! حجم جدید: :size — بکاپ: :backup',
        'compress.lightbox.original' => 'تصویر اصلی — :name (:size)',

        'bulk.confirm' => 'فشرده‌سازی :count فایل با کیفیت :quality%؟',
        'bulk.backup_yes' => 'بکاپ از هر فایل گرفته می‌شود.',
        'bulk.backup_no' => 'بکاپ گرفته نمی‌شود.',
        'bulk.quality_invalid' => 'کیفیت باید بین ۱ تا ۱۰۰ باشد',
        'bulk.processing' => 'در حال پردازش: :name (:current/:total)',
        'bulk.done' => 'تمام شد — موفق: :done، ناموفق: :failed',

        'lightbox.zoom_out' => 'کوچک‌تر',
        'lightbox.zoom_in' => 'بزرگ‌تر',
        'lightbox.zoom_reset' => 'اندازه واقعی',
        'lightbox.zoom_fit' => 'جا شدن در صفحه',
        'lightbox.close' => 'بستن',
        'lightbox.hint' => 'اسکرول برای زوم — درگ برای جابجایی — Esc برای بستن',
        'lightbox.quality' => 'کیفیت :percent% — :size',
        'lightbox.zoom' => 'بزرگ‌نمایی',

        'preview.zoom_label' => 'بزرگ‌نمایی',

        'error.server' => 'خطای سرور',
        'error.invalid_token' => 'توکن درخواست نامعتبر — صفحه را رفرش کنید و دوباره تلاش کنید',
        'error.unauthorized' => 'دسترسی غیرمجاز',
        'error.method_not_allowed' => 'متد مجاز نیست',
        'error.incomplete_data' => 'داده ناقص است',
        'error.path_forbidden' => 'مسیر مجاز نیست',
        'error.file_not_found' => 'فایل یافت نشد',
        'error.invalid_image_format' => 'فرمت تصویر نامعتبر است',
        'error.base64_decode' => 'دیکد base64 ناموفق',
        'error.backup_dir' => 'ایجاد پوشه بکاپ ناموفق بود',
        'error.backup_create' => 'ایجاد بکاپ ناموفق بود',
        'error.save_failed' => 'ذخیره فایل ناموفق بود',
        'error.save' => 'ذخیره ناموفق',
        'error.image_load' => 'بارگذاری تصویر ناموفق',
        'error.compress_failed' => 'فشرده‌سازی ناموفق',
        'error.locale_invalid' => 'زبان نامعتبر است',
    ],
];
