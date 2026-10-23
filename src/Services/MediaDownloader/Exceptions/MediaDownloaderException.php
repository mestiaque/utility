<?php

namespace ME\Utility\Services\MediaDownloader\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Carries a machine-readable reason plus a message that is safe to show to users.
 * Technical details (stderr, exit codes) stay in $technical and are only logged.
 */
class MediaDownloaderException extends RuntimeException
{
    public const INVALID_URL        = 'invalid_url';
    public const BLOCKED_URL        = 'blocked_url';
    public const UNSUPPORTED_URL    = 'unsupported_url';
    public const PRIVATE_MEDIA      = 'private_media';
    public const LOGIN_REQUIRED     = 'login_required';
    public const REMOVED            = 'removed';
    public const UNAVAILABLE        = 'unavailable';
    public const EXTRACTION_FAILED  = 'extraction_failed';
    public const FFMPEG_FAILED      = 'ffmpeg_failed';
    public const TIMEOUT            = 'timeout';
    public const NETWORK_ERROR      = 'network_error';
    public const DISK_SPACE         = 'disk_space';
    public const PERMISSION_DENIED  = 'permission_denied';
    public const UNSUPPORTED_FORMAT = 'unsupported_format';
    public const TOO_LARGE          = 'too_large';
    public const BINARY_MISSING     = 'binary_missing';
    public const CANCELLED          = 'cancelled';
    public const BLOCKED_BY_SITE    = 'blocked_by_site';
    public const SITE_CHANGED       = 'site_changed';
    public const TELEGRAM_APP_ONLY  = 'telegram_app_only';

    private const MESSAGES = [
        self::INVALID_URL        => 'Please enter a valid http:// or https:// URL.',
        self::BLOCKED_URL        => 'This URL points to a local or internal network address and cannot be used.',
        self::UNSUPPORTED_URL    => 'This website or URL is not supported.',
        self::PRIVATE_MEDIA      => 'This media is private. To download media your own account can see, add your login cookies (Login cookies button at the top of the page) and try again.',
        self::LOGIN_REQUIRED     => "This media requires signing in. Add your login cookies for this site (Login cookies button at the top of the page), or refresh them if they have expired.",
        self::REMOVED            => 'This media has been removed or deleted.',
        self::UNAVAILABLE        => 'This media is currently unavailable.',
        self::EXTRACTION_FAILED  => "Unable to download this media.\nThe content may be unavailable, private, unsupported, or temporarily inaccessible.",
        self::FFMPEG_FAILED      => 'The media was downloaded but could not be processed (FFmpeg failed).',
        self::TIMEOUT            => "The download stopped making progress (or ran past the maximum time) and was stopped.\nThe site may be slow right now — please try again later.",
        self::NETWORK_ERROR      => 'A network error occurred while contacting the media site. Please try again.',
        self::DISK_SPACE         => 'The server does not have enough free disk space for this download.',
        self::PERMISSION_DENIED  => 'The server could not write the download (permission error).',
        self::UNSUPPORTED_FORMAT => 'The selected format is not available for this media.',
        self::TOO_LARGE          => 'This file exceeds the maximum allowed download size.',
        self::BINARY_MISSING     => 'The media downloader is not installed correctly on the server (yt-dlp / FFmpeg missing).',
        self::CANCELLED          => 'The download was cancelled.',
        self::SITE_CHANGED       => "The downloader (yt-dlp) could not read this page, even with your login cookies. The site has probably changed its page format.\nCheck that the video plays in your browser, then update yt-dlp (php artisan utility:media-version) and try again later.",
        self::TELEGRAM_APP_ONLY  => "No downloadable videos found on this Telegram channel's public web page.\nThe channel may be private, or its videos are too big for Telegram's web preview (they open only in the Telegram app). Try a single post link (t.me/<channel>/<post>) instead.",
        self::BLOCKED_BY_SITE    => "The site refused the request (HTTP 403/429). It is probably limiting automated downloads from this server for now.\nWait a while and use Retry, download fewer items at a time, or add your login cookies for this site.",
    ];

    public function __construct(
        public readonly string $reason,
        public readonly ?string $technical = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(self::MESSAGES[$reason] ?? self::MESSAGES[self::EXTRACTION_FAILED], 0, $previous);
    }

    public static function because(string $reason, ?string $technical = null, ?Throwable $previous = null): self
    {
        return new self($reason, $technical, $previous);
    }

    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
