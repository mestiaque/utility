<?php

namespace ME\Utility\Services\MediaDownloader;

use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException as E;

/**
 * Maps yt-dlp / FFmpeg stderr to a MediaDownloaderException reason. Order matters:
 * the first matching rule wins, so more specific causes are listed first.
 */
class ErrorClassifier
{
    private const RULES = [
        E::DISK_SPACE         => ['no space left on device', 'errno 28', 'disk quota exceeded'],
        E::PERMISSION_DENIED  => ['permission denied', 'errno 13', 'read-only file system'],
        E::PRIVATE_MEDIA      => ['private video', 'video is private', 'this account is private', 'private account', 'content is private'],
        E::REMOVED            => ['has been removed', 'been deleted', 'no longer available', 'account has been terminated', 'http error 404', 'http error 410'],
        E::LOGIN_REQUIRED     => ['sign in', 'login required', 'log in', 'logged-in', 'login_required', 'confirm your age', 'age-restricted', 'use --cookies', 'authentication'],
        E::TOO_LARGE          => ['max-filesize', 'larger than max'],
        E::UNSUPPORTED_URL    => ['unsupported url', 'no video formats found', 'no suitable extractor'],
        E::UNSUPPORTED_FORMAT => ['requested format is not available', 'requested format not available'],
        E::FFMPEG_FAILED      => ['ffmpeg', 'ffprobe', 'postprocessing', 'conversion failed', 'merging of multiple formats'],
        E::BLOCKED_BY_SITE    => ['http error 403', 'http error 429', 'too many requests', 'rate-limit', 'rate limit'],
        E::TIMEOUT            => ['timed out', 'timeout'],
        E::NETWORK_ERROR      => ['network is unreachable', 'name or service not known', 'temporary failure in name resolution',
                                  'connection refused', 'connection reset', 'unable to download webpage', 'getaddrinfo',
                                  'ssl:', 'remote end closed connection', 'failed to resolve'],
        E::UNAVAILABLE        => ['video unavailable', 'not available', 'unavailable', 'geo restrict'],
    ];

    public function classify(string $stderr, ?int $exitCode = null): string
    {
        // 127 = shell could not find the configured binary
        if ($exitCode === 127) {
            return E::BINARY_MISSING;
        }

        $haystack = strtolower($stderr);

        // Facebook serves logged-out visitors a login wall instead of the video page for content
        // that needs an account (friends-only, groups…); yt-dlp then reports "Cannot parse data".
        if (str_contains($haystack, '[facebook]') && str_contains($haystack, 'cannot parse data')) {
            return E::LOGIN_REQUIRED;
        }

        foreach (self::RULES as $reason => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return $reason;
                }
            }
        }

        return E::EXTRACTION_FAILED;
    }

    public function exception(string $stderr, ?int $exitCode = null): E
    {
        return E::because($this->classify($stderr, $exitCode), trim("exit={$exitCode} " . self::lastLines($stderr)));
    }

    private static function lastLines(string $text, int $lines = 15): string
    {
        return implode("\n", array_slice(preg_split('/\R/', trim($text)), -$lines));
    }
}
