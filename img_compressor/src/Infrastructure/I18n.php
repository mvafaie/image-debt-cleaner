<?php

declare(strict_types=1);

namespace ImgCompressor\Infrastructure;

use ImgCompressor\Config\AppConfig;

final class I18n
{
    private string $locale;
    private string $fallbackLocale;
    /** @var array<string, string> */
    private array $messages = [];
    /** @var array<string, string> */
    private array $fallbackMessages = [];
    /** @var array<string, array{locale: string, direction: string, label: string}> */
    private array $availableLocales = [];

    private function __construct(
        private readonly AppConfig $config,
        private readonly string $langDir,
    ) {
        $i18n = $this->config->all()['i18n'] ?? [];
        $this->fallbackLocale = $this->normalizeLocale($i18n['fallback_locale'] ?? 'en');
        $this->availableLocales = $this->buildAvailableLocales($i18n['available_locales'] ?? []);
    }

    public static function boot(AppConfig $config, string $langDir): self
    {
        $instance = new self($config, $langDir);
        $instance->setLocale($instance->resolveLocale());

        return $instance;
    }

    public function resolveLocale(): string
    {
        $i18n = $this->config->all()['i18n'] ?? [];
        $available = array_keys($this->availableLocales);

        if (!empty($_GET['lang'])) {
            $candidate = $this->normalizeLocale((string) $_GET['lang']);
            if ($this->isAvailable($candidate)) {
                $_SESSION['img_compressor_locale'] = $candidate;

                return $candidate;
            }
        }

        if (!empty($i18n['locale'])) {
            $candidate = $this->normalizeLocale((string) $i18n['locale']);
            if ($this->isAvailable($candidate)) {
                return $candidate;
            }
        }

        if (!empty($_SESSION['img_compressor_locale'])) {
            $candidate = $this->normalizeLocale((string) $_SESSION['img_compressor_locale']);
            if ($this->isAvailable($candidate)) {
                return $candidate;
            }
        }

        $default = $this->normalizeLocale($i18n['default_locale'] ?? $this->fallbackLocale);
        if ($this->isAvailable($default)) {
            return $default;
        }

        $preferred = $this->parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        foreach ($preferred as $candidate) {
            if ($this->isAvailable($candidate)) {
                return $candidate;
            }
        }

        return $this->isAvailable($this->fallbackLocale) ? $this->fallbackLocale : ($available[0] ?? 'en');
    }

    public function setLocale(string $locale): bool
    {
        $locale = $this->normalizeLocale($locale);
        if (!$this->isAvailable($locale)) {
            return false;
        }

        $this->locale = $locale;
        $this->messages = $this->loadLocaleFile($locale);
        $this->fallbackMessages = $locale === $this->fallbackLocale
            ? []
            : $this->loadLocaleFile($this->fallbackLocale);

        $_SESSION['img_compressor_locale'] = $locale;

        return true;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getDirection(): string
    {
        return $this->availableLocales[$this->locale]['direction'] ?? 'ltr';
    }

    /** @return array<string, array{locale: string, direction: string, label: string}> */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /** @return array<string, string> */
    public function getMessages(): array
    {
        return array_merge($this->fallbackMessages, $this->messages);
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'locale' => $this->getLocale(),
            'dir' => $this->getDirection(),
            'fallback_locale' => $this->config->fallbackLocale(),
            'available_locales' => $this->getAvailableLocales(),
            'messages' => $this->getMessages(),
        ];
    }

    public function get(string $key, array $replace = []): string
    {
        $message = $this->messages[$key]
            ?? $this->fallbackMessages[$key]
            ?? $key;

        foreach ($replace as $name => $value) {
            $message = str_replace(':' . $name, (string) $value, $message);
        }

        return $message;
    }

    /** @return list<string> */
    private function parseAcceptLanguage(string $header): array
    {
        $locales = [];
        foreach (explode(',', $header) as $part) {
            $part = trim(explode(';', $part)[0]);
            if ($part === '') {
                continue;
            }
            $locales[] = $this->normalizeLocale(str_replace('-', '_', $part));
            if (str_contains($part, '-')) {
                $locales[] = $this->normalizeLocale(explode('-', $part)[0]);
            }
        }

        return array_values(array_unique($locales));
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', trim($locale)));

        return match ($locale) {
            'fa-ir', 'fa-af' => 'fa',
            'en-us', 'en-gb', 'en-au' => 'en',
            default => explode('-', $locale)[0],
        };
    }

    /** @param array<string, array<string, string>> $configured */
    private function buildAvailableLocales(array $configured): array
    {
        $locales = [];
        foreach ($configured as $code => $meta) {
            $code = $this->normalizeLocale($code);
            $fileMeta = $this->readMetaFromFile($code);
            $locales[$code] = [
                'locale' => $code,
                'direction' => $meta['dir'] ?? $fileMeta['direction'] ?? 'ltr',
                'label' => $meta['native'] ?? $meta['name'] ?? $fileMeta['label'] ?? $code,
            ];
        }

        if ($locales === []) {
            $locales['en'] = ['locale' => 'en', 'direction' => 'ltr', 'label' => 'English'];
        }

        return $locales;
    }

    /** @return array{direction?: string, label?: string} */
    private function readMetaFromFile(string $locale): array
    {
        $path = $this->langDir . '/' . $locale . '.php';
        if (!is_file($path)) {
            return [];
        }
        $data = require $path;

        return [
            'direction' => $data['meta']['direction'] ?? null,
            'label' => $data['meta']['label'] ?? null,
        ];
    }

    private function isAvailable(string $locale): bool
    {
        return isset($this->availableLocales[$locale])
            && is_file($this->langDir . '/' . $locale . '.php');
    }

    /** @return array<string, string> */
    private function loadLocaleFile(string $locale): array
    {
        $path = $this->langDir . '/' . $locale . '.php';
        if (!is_file($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data['strings'] ?? null) ? $data['strings'] : [];
    }
}
