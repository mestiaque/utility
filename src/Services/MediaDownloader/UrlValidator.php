<?php

namespace ME\Utility\Services\MediaDownloader;

use Closure;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;

/**
 * Guards every URL handed to yt-dlp against SSRF:
 * http/https only, no credentials, default ports only, and every resolved IP must be public.
 */
class UrlValidator
{
    private const MAX_LENGTH       = 2048;
    private const ALLOWED_SCHEMES  = ['http', 'https'];
    private const ALLOWED_PORTS    = [80, 443];
    private const BLOCKED_SUFFIXES = ['.localhost', '.local', '.internal', '.intranet', '.lan', '.home.arpa', '.localdomain'];

    /** @var Closure(string): string[] */
    private Closure $resolver;

    /**
     * @param string[] $allowedHosts empty = any public host
     * @param (Closure(string): string[])|null $resolver host → list of IPs (injectable for tests)
     */
    public function __construct(
        private readonly array $allowedHosts = [],
        ?Closure $resolver = null,
    ) {
        $this->resolver = $resolver ?? static fn (string $host): array => self::resolveHost($host);
    }

    /**
     * Returns the normalized URL or throws MediaDownloaderException (INVALID_URL / BLOCKED_URL / UNSUPPORTED_URL).
     */
    public function validate(string $url): string
    {
        $url = trim($url);

        if ($url === '' || strlen($url) > self::MAX_LENGTH || preg_match('/[\s\x00-\x1F\x7F]/', $url)) {
            throw MediaDownloaderException::because(MediaDownloaderException::INVALID_URL, "Malformed URL: {$url}");
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw MediaDownloaderException::because(MediaDownloaderException::INVALID_URL, "Unparsable URL: {$url}");
        }

        if (! in_array(strtolower($parts['scheme']), self::ALLOWED_SCHEMES, true)) {
            throw MediaDownloaderException::because(MediaDownloaderException::INVALID_URL, "Scheme not allowed: {$parts['scheme']}");
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw MediaDownloaderException::because(MediaDownloaderException::INVALID_URL, 'Credentials in URL are not allowed');
        }

        if (isset($parts['port']) && ! in_array((int) $parts['port'], self::ALLOWED_PORTS, true)) {
            throw MediaDownloaderException::because(MediaDownloaderException::BLOCKED_URL, "Port not allowed: {$parts['port']}");
        }

        $host = rtrim(strtolower(trim($parts['host'], '[]')), '.');

        $this->assertHostNameAllowed($host);
        $this->assertResolvesToPublicAddresses($host);

        return $url;
    }

    public function isValid(string $url): bool
    {
        try {
            $this->validate($url);

            return true;
        } catch (MediaDownloaderException) {
            return false;
        }
    }

    public static function isPublicIp(string $ip): bool
    {
        // IPv4-mapped / -compatible IPv6 (::ffff:127.0.0.1) → check the embedded IPv4
        if (preg_match('/^::(?:ffff:)?(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)) {
            $ip = $m[1];
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE
        ) !== false;
    }

    // ── Internals ──────────────────────────────────────────────────────────────

    private function assertHostNameAllowed(string $host): void
    {
        // Single-label names ("intranet", "db") are LAN names; IPv6 literals are checked after resolution
        $isSingleLabel = ! str_contains($host, '.') && filter_var($host, FILTER_VALIDATE_IP) === false;

        if ($host === '' || $host === 'localhost' || $isSingleLabel) {
            throw MediaDownloaderException::because(MediaDownloaderException::BLOCKED_URL, "Local host name: {$host}");
        }

        foreach (self::BLOCKED_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                throw MediaDownloaderException::because(MediaDownloaderException::BLOCKED_URL, "Local host name: {$host}");
            }
        }

        if ($this->allowedHosts === []) {
            return;
        }

        foreach ($this->allowedHosts as $allowed) {
            $allowed = strtolower(trim($allowed));
            if ($allowed !== '' && ($host === $allowed || str_ends_with($host, '.' . $allowed))) {
                return;
            }
        }

        throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_URL, "Host not in allow-list: {$host}");
    }

    private function assertResolvesToPublicAddresses(string $host): void
    {
        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : ($this->resolver)($host);

        if ($ips === []) {
            throw MediaDownloaderException::because(MediaDownloaderException::INVALID_URL, "Host does not resolve: {$host}");
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                throw MediaDownloaderException::because(MediaDownloaderException::BLOCKED_URL, "Host {$host} resolves to non-public IP {$ip}");
            }
        }
    }

    /** @return string[] */
    private static function resolveHost(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];

        $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($aaaa as $record) {
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
