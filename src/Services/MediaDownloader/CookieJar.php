<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * A user's own login cookies (Netscape cookies.txt) so yt-dlp can download media that
 * account is allowed to see (private / friends-only / group posts).
 *
 * Stored encrypted with APP_KEY, never returned to the browser or logged, and only written
 * in plain text to a 0600 temp file for the lifetime of one yt-dlp run.
 */
class CookieJar
{
    public const MAX_BYTES = 1024 * 1024;

    /** Cookie names that only exist while logged in, per site (any one of them is enough). */
    private const SESSION_COOKIES = [
        'facebook.com'  => ['c_user', 'xs'],
        'instagram.com' => ['sessionid'],
        'youtube.com'   => ['__Secure-3PSID', 'SAPISID', 'LOGIN_INFO'],
        'google.com'    => ['__Secure-3PSID', 'SAPISID'],
        'tiktok.com'    => ['sessionid', 'sessionid_ss'],
        'x.com'         => ['auth_token'],
        'twitter.com'   => ['auth_token'],
        'pinterest.com' => ['_pinterest_sess'],
    ];

    public function __construct(
        private readonly Encrypter $encrypter,
        private readonly string $directory,
    ) {
    }

    public function has(?int $userId): bool
    {
        return $userId !== null && is_file($this->path($userId));
    }

    /**
     * Validates and stores the cookies. Returns the cookie domains found.
     *
     * @return string[]
     */
    public function put(int $userId, string $contents): array
    {
        $domains = self::parseDomains($contents);

        if (strlen($contents) > self::MAX_BYTES || $domains === []) {
            throw new \InvalidArgumentException('This is not a valid cookies.txt file (Netscape format).');
        }

        MediaDownloadService::makeDirectory($this->directory, 0770);

        $payload = $this->encrypter->encrypt([
            'cookies'    => $contents,
            'domains'    => $domains,
            'updated_at' => now()->toIso8601String(),
        ]);

        File::put($this->path($userId), $payload);
        @chmod($this->path($userId), 0660);

        return $domains;
    }

    public function forget(int $userId): void
    {
        File::delete($this->path($userId));
    }

    /**
     * Metadata only — never the cookie values.
     *
     * "sessions" says, for known sites, whether a non-expired login cookie is present, so a
     * cookies.txt exported while logged out (or long expired) is easy to spot.
     *
     * @return array{domains: string[], updated_at: ?Carbon, sessions: array<string, bool>}|null
     */
    public function summary(?int $userId): ?array
    {
        $data = $this->read($userId);

        return $data === null ? null : [
            'domains'    => $data['domains'] ?? [],
            'updated_at' => isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null,
            'sessions'   => self::loginSessions($data['cookies']),
        ];
    }

    /** @return array<string, bool> site => has a valid login cookie (only sites present in the file) */
    public static function loginSessions(string $contents, ?int $now = null): array
    {
        $now   ??= time();
        $found = [];

        foreach (preg_split('/\R/', $contents) as $line) {
            $line = preg_replace('/^#HttpOnly_/', '', rtrim(ltrim($line), "\r\n "));
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $f = explode("\t", $line);
            if (count($f) < 7) {
                continue;
            }

            $domain = ltrim(strtolower($f[0]), '.');
            foreach (self::SESSION_COOKIES as $site => $names) {
                if ($domain !== $site && ! str_ends_with($domain, '.' . $site)) {
                    continue;
                }

                $found[$site] ??= false;
                $expires = (int) $f[4];             // 0 = browser-session cookie
                if (in_array($f[5], $names, true) && trim($f[6]) !== '' && ($expires === 0 || $expires > $now)) {
                    $found[$site] = true;
                }
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * Writes a temporary plain-text copy for yt-dlp (which may also rewrite it), runs $callback
     * with its path (or null when the user has no cookies), and always deletes it afterwards.
     *
     * @template T
     * @param callable(?string): T $callback
     * @return T
     */
    public function withTemporaryFile(?int $userId, string $directory, callable $callback): mixed
    {
        $data = $this->read($userId);

        if ($data === null) {
            return $callback(null);
        }

        MediaDownloadService::makeDirectory($directory, 0770);
        $path = rtrim($directory, '/') . '/.cookies-' . bin2hex(random_bytes(8)) . '.txt';

        try {
            File::put($path, $data['cookies']);
            @chmod($path, 0600);

            return $callback($path);
        } finally {
            File::delete($path);
        }
    }

    /** Fingerprint used to keep analysis caches separate per login state. */
    public function fingerprint(?int $userId): string
    {
        return $this->has($userId) ? 'u' . $userId . '-' . filemtime($this->path($userId)) : 'public';
    }

    /** @return string[] distinct cookie domains, empty when the text is not a cookies.txt */
    public static function parseDomains(string $contents): array
    {
        $domains = [];

        foreach (preg_split('/\R/', $contents) as $line) {
            // "#HttpOnly_" prefixed lines are real cookies; other "#" lines are comments
            // (only strip line breaks/spaces: an empty cookie value leaves a meaningful trailing tab)
            $line = preg_replace('/^#HttpOnly_/', '', rtrim(ltrim($line), "\r\n "));
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $fields = explode("\t", $line);
            if (count($fields) < 7 || ! in_array(strtoupper($fields[1]), ['TRUE', 'FALSE'], true)) {
                return []; // not Netscape format
            }

            $domains[ltrim(strtolower($fields[0]), '.')] = true;
        }

        $domains = array_keys($domains);
        sort($domains);

        return $domains;
    }

    private function read(?int $userId): ?array
    {
        if (! $this->has($userId)) {
            return null;
        }

        try {
            $data = $this->encrypter->decrypt(File::get($this->path($userId)));
        } catch (\Throwable) {
            return null; // APP_KEY changed or file damaged — treat as "no cookies"
        }

        return is_array($data) && isset($data['cookies']) ? $data : null;
    }

    private function path(int $userId): string
    {
        return rtrim($this->directory, '/') . '/user-' . $userId . '.enc';
    }
}
