# Image Debt Cleaner

> Scan, preview, and compress oversized images to reclaim storage space in any PHP application.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php\&logoColor=white)
![Dependencies](https://img.shields.io/badge/dependencies-none-brightgreen)
![License](https://img.shields.io/badge/license-MIT-blue)

Originally built to clean up years of oversized uploads in a legacy PHP project, but works with any PHP application.

No framework required.

No database required.

No ImageMagick required.

No deployment changes required.

---

## Demo

**File list** — oversized images sorted by size, bulk selection, and pagination:

![File list view](img_compressor/assets/docs/list.png)

**Compression preview** — original image, quality levels, and before/after size comparison:

![Single file compression view](img_compressor/assets/docs/single.png)

```text
Largest Images
      ↓
Preview Compression Levels
      ↓
Compare File Sizes
      ↓
Backup Original
      ↓
Save Optimized Version
```

---

## Why This Exists

I inherited a legacy PHP application where users had been uploading images for years.

There were no upload limits, no compression pipeline, and no cleanup process.

The application worked fine, but storage usage kept growing.

Thousands of images had accumulated over time, many of them between 3 MB and 10 MB each.

Backups became larger.

Deployments became slower.

Storage costs increased.

Pages loaded heavier images than necessary.

I needed a practical solution that could be deployed immediately without redesigning the existing upload workflow.

So I built this tool.

It worked — and I open-sourced it because this problem is everywhere.

---

## Does This Sound Familiar?

This tool may help if:

* Your project contains years of uploaded images
* Storage usage keeps growing
* Backups are becoming too large
* Product, document, or gallery pages load oversized images
* You inherited a legacy application with no image optimization process
* You need a quick cleanup solution before implementing a long-term media strategy
* You want an admin-only utility for image optimization

---

## Features

### Image Discovery

* Scan directories recursively
* Detect oversized images
* Sort by file size
* Thumbnail previews
* Configurable size thresholds

### Compression Preview

* Multiple quality presets
* Before/after comparison
* Real-time size estimation
* Compression statistics
* Visual quality inspection

### Batch Processing

* Compress multiple images at once
* Progress tracking
* Bulk selection
* Consistent quality settings

### Safety Features

* Optional backup creation
* File validation
* Path protection (`realpath` validation under configured scan roots)
* Session-based authentication
* CSRF protection on login and save
* Login rate limiting (brute-force lockout)
* Upload size limits on save API

### User Experience

* Responsive interface
* Dark mode
* English and Persian language support
* RTL support
* Sticky progress notifications
* Image lightbox viewer

---

## Before & After

Example result:

```text
Original Image: 5.8 MB
Compressed:     680 KB
Reduction:      88%
```

Real-world cleanup projects often recover several gigabytes of storage from old uploads.

---

## Requirements

* PHP 8.1 or newer
* Apache, Nginx, or PHP built-in server
* Modern browser with Canvas API support

Optional:

* Writable backup directory

---

## Quick Start

1. Copy the `img_compressor/` directory to your server
2. Configure a password
3. Open the tool in your browser
4. Start with the largest images
5. Preview results
6. Save optimized versions

---

## Installation

### Directory Structure

Place `img_compressor/` anywhere under your web root — paths are resolved automatically:

```text
public/                          ← document root (auto-detected)
├── img/                         ← scan_paths target
│   └── documents/
├── uploads/
└── admin/tools/img_compressor/  ← tool works here too
```

`scan_paths` are relative to the **document root**, not the tool folder.

### Configure Access

Edit:

```php
img_compressor/config.php
```

Set a secure password:

```php
'password' => 'your-secure-password',
```

### Launch

Open the tool at whatever URL matches its folder:

```text
https://your-domain.com/img_compressor/
https://your-domain.com/admin/tools/img_compressor/
```

Asset URLs, API calls, and image paths adjust automatically. Override only if needed:

```php
'document_root' => '/var/www/public',           // absolute web root
'base_path' => '/admin/tools/img_compressor/',  // URL prefix behind reverse proxy
'assets_url_prefix' => '/',                     // public URL prefix for images
```

The API endpoint is a relative `index.php` — resolved against the current page URL, so it works in any subdirectory. If the server returns a non-JSON response (e.g. 404 HTML), the UI shows a clear error like `Server error (HTTP 404)` instead of a JSON parse failure.

Or using PHP's built-in server:

```bash
php -S localhost:8080 -t /path/to/public
```

---

## How It Works

```text
Images On Disk
       │
       ▼
Directory Scan
       │
       ▼
Image Preview
       │
       ▼
Browser Compression
       │
       ▼
Compare Results
       │
       ▼
Create Backup
       │
       ▼
Save Optimized Image
```

Important:

Compression happens in the browser using the Canvas API.

The server handles:

* Authentication
* File scanning
* Backups
* Saving optimized files

No server-side image processing libraries are required.

---

## Architecture

Layered, dependency-free PHP — no framework, no Composer at runtime.

```text
Browser (vanilla JS)
 ├─ Image preview & compression (Canvas API)
 ├─ Bulk operations
 └─ Upload optimized image (base64)

             │  ?action=login|files|save|check|locale
             ▼

img_compressor/
 ├── index.php          → front controller
 ├── bootstrap.php      → wiring & autoload
 ├── views/app.php      → HTML shell
 └── src/
      ├── Http/         → Request, Router, JSON responses
      ├── Application/  → use cases (one action per class)
      ├── Domain/       → ImageFile, formatters
      └── Infrastructure/
           ├── PathGuard    → all filesystem security
           ├── FileScanner  → directory scan
           ├── BackupStore  → timestamped backups
           └── SessionAuth  → login, CSRF, sessions
```

Full design rationale, security model, and extension guide: **[ARCHITECTURE.md](ARCHITECTURE.md)**

---

## Security

**Implemented protections:**

* Password-protected access (`password_hash` / bcrypt supported via `config.local.php`)
* Session-based authentication
* CSRF tokens on login and save
* Login rate limiting (lockout after failed attempts)
* Path traversal protection via `PathGuard` (`realpath` + scan-root validation)
* File type and upload size validation
* HttpOnly + SameSite session cookies; `Secure` flag on HTTPS
* Security headers (CSP, `X-Frame-Options`, etc.)
* `.htaccess` blocks direct access to `config.php`, `bootstrap.php`, `src/`, `views/`, and `lang/`
* Backup directory isolated from web access

**Before production:**

* Move secrets to `config.local.php` (see `config.example.php`)
* Use HTTPS
* Restrict access to administrators (IP allowlist / VPN recommended)
* Limit `scan_paths` to folders you actually need

Recommendations:

* Restrict access to administrators only
* Use HTTPS in production
* Store backups outside public access when possible
* Use a strong password (hashed, not plain text in repo)

---

## Project Structure

```text
image-compressor/
├── ARCHITECTURE.md          # design rationale & extension guide
├── README.md
└── img_compressor/
    ├── index.php            # front controller (entry point)
    ├── bootstrap.php        # autoload & dependency wiring
    ├── config.php           # deployment settings (gitignored)
    ├── config.example.php
    ├── views/
    │   └── app.php          # HTML shell
    ├── src/
    │   ├── Config/          # AppConfig
    │   ├── Domain/          # ImageFile, ByteFormatter
    │   ├── Http/            # Request, Router, JsonResponse
    │   ├── Application/     # use cases (List, Save, ShowApp)
    │   └── Infrastructure/  # PathGuard, FileScanner, SessionAuth, I18n
    ├── lang/
    │   ├── en.php
    │   └── fa.php
    └── assets/
        ├── css/
        ├── js/
        └── backups/
```

---

## Roadmap

* [ ] WebP export support
* [ ] AVIF export support
* [ ] Folder-level statistics dashboard
* [ ] Automatic backup cleanup
* [ ] Command-line interface (CLI)
* [ ] Drag-and-drop optimization
* [ ] Compression history
* [ ] Multi-user authentication
* [ ] Scheduled optimization tasks

---

## Documentation

| Document | Contents |
|----------|----------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Layer design, request flow, security model, extension guide |
| `config.example.php` | All configuration options with comments |
| `lang/en.php`, `lang/fa.php` | Translation strings and locale metadata |

### API endpoints

All endpoints use `index.php?action=…`:

| Action | Method | Auth | Description |
|--------|--------|------|-------------|
| `check` | GET | — | Session status, CSRF token, i18n payload |
| `login` | POST | — | Authenticate with password |
| `logout` | GET | — | Destroy session |
| `locale` | GET/POST | — | Switch language (`lang=en\|fa`) |
| `files` | GET | ✓ | List oversized images (paginated) |
| `save` | POST | ✓ | Write compressed image (+ optional backup) |
| *(none)* | GET | — | Render the HTML UI |

---

## Limitations

* Compression output is JPEG
* Transparent PNG images may lose transparency
* Extremely large images may exceed browser memory limits
* Backup files are not automatically removed
* Not intended as a replacement for a complete media management system

---

## Contributing

Contributions are welcome.

Bug reports, feature requests, pull requests, and ideas are appreciated.

Please keep the project:

* Lightweight
* Dependency-free
* Easy to deploy
* Framework-agnostic
* Focused on solving image storage debt

---

## Why Open Source?

This project started as a practical solution to a real production problem.

After successfully reducing storage usage in a legacy application, it became clear that many teams face the same challenge:

Years of uploaded images with no optimization strategy.

Instead of keeping the tool private, I decided to share it with the community.

If it helps you recover storage space, simplify backups, or improve performance, then it has achieved its purpose.

---

## License

MIT License
