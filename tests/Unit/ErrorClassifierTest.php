<?php

namespace ME\Utility\Tests\Unit;

use ME\Utility\Services\MediaDownloader\ErrorClassifier;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException as E;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ErrorClassifierTest extends TestCase
{
    #[DataProvider('messages')]
    public function test_classifies_yt_dlp_errors(string $stderr, string $expected, ?int $exit = 1): void
    {
        $this->assertSame($expected, (new ErrorClassifier())->classify($stderr, $exit));
    }

    public static function messages(): array
    {
        return [
            'private'      => ['ERROR: [youtube] abc: Private video. Sign in if you\'ve been granted access', E::PRIVATE_MEDIA],
            'login'        => ['ERROR: [instagram] x: Requested content is not available, rate-limit reached or login required', E::LOGIN_REQUIRED],
            'removed'      => ['ERROR: [youtube] abc: Video unavailable. This video has been removed by the uploader', E::REMOVED],
            'unavailable'  => ['ERROR: [youtube] abc: Video unavailable', E::UNAVAILABLE],
            'unsupported'  => ['ERROR: Unsupported URL: https://example.com/', E::UNSUPPORTED_URL],
            'format'       => ['ERROR: Requested format is not available', E::UNSUPPORTED_FORMAT],
            'ffmpeg'       => ['ERROR: Postprocessing: Conversion failed!', E::FFMPEG_FAILED],
            'network'      => ['ERROR: Unable to download webpage: <urlopen error [Errno -2] Name or service not known>', E::NETWORK_ERROR],
            'timeout'      => ['ERROR: Read timed out.', E::TIMEOUT],
            'disk'         => ['ERROR: unable to write data: [Errno 28] No space left on device', E::DISK_SPACE],
            'permission'   => ['ERROR: unable to open for writing: [Errno 13] Permission denied', E::PERMISSION_DENIED],
            'too large'    => ['File is larger than max-filesize (3000 bytes > 1000 bytes). Aborting.', E::TOO_LARGE],
            'blocked 403'  => ['ERROR: [TikTok] 7199: Unable to download webpage: HTTP Error 403: Forbidden', E::BLOCKED_BY_SITE],
            'blocked 429'  => ['ERROR: HTTP Error 429: Too Many Requests', E::BLOCKED_BY_SITE],
            'fb login wall'=> ['ERROR: [facebook] 2244467976339471: Cannot parse data; please report this issue', E::LOGIN_REQUIRED],
            'unknown'      => ['something odd happened', E::EXTRACTION_FAILED],
            'binary'       => ['sh: 1: exec: yt-dlp: not found', E::BINARY_MISSING, 127],
        ];
    }

    public function test_exception_keeps_technical_detail_out_of_user_message(): void
    {
        $e = (new ErrorClassifier())->exception("ERROR: secret /var/www/path trace\nPrivate video", 1);

        $this->assertSame(E::PRIVATE_MEDIA, $e->reason);
        $this->assertStringNotContainsString('/var/www', $e->userMessage());
        $this->assertStringContainsString('/var/www', $e->technical);
    }
}
