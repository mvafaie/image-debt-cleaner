<?php

declare(strict_types=1);

namespace ImgCompressor\Infrastructure;

use ImgCompressor\Config\AppConfig;
use ImgCompressor\Http\JsonResponse;
use ImgCompressor\Http\Request;

final class SessionAuth
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
        private readonly I18n $i18n,
    ) {
    }

    public static function startSession(AppConfig $config, AppPaths $paths): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name('imgcomp_sess');
        session_set_cookie_params([
            'lifetime' => $config->sessionLifetime(),
            'path' => $paths->sessionCookiePath(),
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf(Request $request): void
    {
        $token = $request->post('csrf_token') ?? $request->server('HTTP_X_CSRF_TOKEN') ?? '';
        if ($token === '' || !hash_equals($this->csrfToken(), (string) $token)) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.invalid_token')], 403);
        }
    }

    public function checkUserAgent(): bool
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        return $ua !== '' && str_contains($ua, $this->config->allowedUserAgent());
    }

    public function requireAccess(): void
    {
        if ($this->checkUserAgent()) {
            return;
        }

        http_response_code(403);
        $lang = $this->i18n->getLocale();
        $dir = $this->i18n->getDirection();
        $title = htmlspecialchars($this->i18n->get('auth.forbidden'), ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="' . $lang . '" dir="' . $dir . '"><head><meta charset="UTF-8"><title>403</title></head><body><h1>' . $title . '</h1></body></html>';
        exit;
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['img_compressor_auth'])
            && $_SESSION['img_compressor_auth'] === true
            && !empty($_SESSION['img_compressor_expires'])
            && $_SESSION['img_compressor_expires'] > time();
    }

    public function assertLoginAllowed(): void
    {
        $now = time();

        if (!empty($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > $now) {
            $wait = $_SESSION['login_locked_until'] - $now;
            JsonResponse::send(['ok' => false, 'error' => "Too many attempts. Try again in {$wait}s."], 429);
        }

        if (!empty($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] <= $now) {
            unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
        }
    }

    private function verifyPassword(string $input): bool
    {
        $hash = $this->config->get('password_hash');
        if (!empty($hash)) {
            return password_verify($input, (string) $hash);
        }

        $password = $this->config->get('password');
        if (!empty($password)) {
            return hash_equals((string) $password, $input);
        }

        return false;
    }

    private function recordFailedLogin(): void
    {
        $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;

        if ($_SESSION['login_attempts'] >= $this->config->loginMaxAttempts()) {
            $_SESSION['login_locked_until'] = time() + $this->config->loginLockoutSeconds();
        }
    }

    private function clearLoginAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
    }

    public function login(Request $request): void
    {
        $this->verifyCsrf($request);
        $this->assertLoginAllowed();

        $password = (string) $request->post('password', '');
        if ($password !== '' && $this->verifyPassword($password)) {
            session_regenerate_id(true);
            $this->clearLoginAttempts();
            $_SESSION['img_compressor_auth'] = true;
            $_SESSION['img_compressor_expires'] = time() + $this->config->sessionLifetime();
            JsonResponse::send(['ok' => true, 'csrf_token' => $this->csrfToken()]);
        }

        $this->recordFailedLogin();
        JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('auth.wrong_password')], 401);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        JsonResponse::send(['ok' => true]);
    }
}
