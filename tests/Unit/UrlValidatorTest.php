<?php

namespace ME\Utility\Tests\Unit;

use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use ME\Utility\Services\MediaDownloader\UrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    private function validator(array $ips = ['93.184.216.34'], array $allowed = []): UrlValidator
    {
        return new UrlValidator($allowed, fn (string $host) => $ips);
    }

    public function test_valid_https_url_passes(): void
    {
        $url = 'https://www.youtube.com/watch?v=abc123';

        $this->assertSame($url, $this->validator()->validate($url));
    }

    public function test_valid_http_url_passes(): void
    {
        $this->assertTrue($this->validator()->isValid('http://vimeo.com/123'));
    }

    #[DataProvider('invalidUrls')]
    public function test_invalid_urls_are_rejected(string $url): void
    {
        $this->assertFalse($this->validator()->isValid($url));
    }

    public static function invalidUrls(): array
    {
        return [
            'empty'        => [''],
            'not a url'    => ['not a url'],
            'no host'      => ['https://'],
            'whitespace'   => ["https://example.com/a b"],
            'file'         => ['file:///etc/passwd'],
            'ftp'          => ['ftp://example.com/video.mp4'],
            'php'          => ['php://filter/resource=index.php'],
            'data'         => ['data:text/plain;base64,SGVsbG8='],
            'javascript'   => ['javascript:alert(1)'],
            'gopher'       => ['gopher://example.com'],
            'credentials'  => ['https://user:pass@example.com/video'],
        ];
    }

    #[DataProvider('blockedHosts')]
    public function test_local_and_private_targets_are_blocked(string $url): void
    {
        try {
            $this->validator()->validate($url);
            $this->fail("{$url} should have been rejected");
        } catch (MediaDownloaderException $e) {
            $this->assertSame(MediaDownloaderException::BLOCKED_URL, $e->reason);
        }
    }

    public static function blockedHosts(): array
    {
        return [
            'localhost'         => ['http://localhost/video'],
            'localhost sub'     => ['http://api.localhost/video'],
            'loopback'          => ['http://127.0.0.1/video'],
            'loopback other'    => ['http://127.1.2.3/video'],
            'zero'              => ['http://0.0.0.0/video'],
            'private 10'        => ['http://10.0.0.5/video'],
            'private 172'       => ['http://172.16.3.4/video'],
            'private 192'       => ['http://192.168.1.10/video'],
            'link local / meta' => ['http://169.254.169.254/latest/meta-data'],
            'cgnat'             => ['http://100.64.0.1/'],
            'ipv6 loopback'     => ['http://[::1]/video'],
            'ipv6 mapped'       => ['http://[::ffff:127.0.0.1]/video'],
            'ipv6 ula'          => ['http://[fd00::1]/video'],
            'single label'      => ['http://intranet/video'],
            'dot local'         => ['http://printer.local/'],
            'odd port'          => ['https://example.com:8080/video'],
        ];
    }

    public function test_public_host_resolving_to_private_ip_is_blocked(): void
    {
        $this->expectException(MediaDownloaderException::class);

        $this->validator(['93.184.216.34', '10.0.0.1'])->validate('https://evil.example.com/video');
    }

    public function test_unresolvable_host_is_invalid(): void
    {
        try {
            $this->validator([])->validate('https://does-not-exist.example/video');
            $this->fail('Expected exception');
        } catch (MediaDownloaderException $e) {
            $this->assertSame(MediaDownloaderException::INVALID_URL, $e->reason);
        }
    }

    public function test_allow_list_restricts_hosts(): void
    {
        $validator = $this->validator(allowed: ['youtube.com', 'tiktok.com']);

        $this->assertTrue($validator->isValid('https://www.youtube.com/watch?v=1'));
        $this->assertTrue($validator->isValid('https://tiktok.com/@a/video/1'));
        $this->assertFalse($validator->isValid('https://notyoutube.com/watch?v=1'));
        $this->assertFalse($validator->isValid('https://vimeo.com/1'));
    }
}
