<?php

namespace ME\Utility\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use ME\Utility\Services\MediaDownloader\YtDlp;

/**
 * Shows the installed yt-dlp version and how to update it for the detected installation
 * method. It never updates anything by itself.
 */
class MediaVersionCommand extends Command
{
    protected $signature = 'utility:media-version
                            {--check-latest : Compare with the latest release on GitHub (network request)}';

    protected $description = 'Show the installed yt-dlp version and update instructions';

    private const RELEASES_API = 'https://api.github.com/repos/yt-dlp/yt-dlp/releases/latest';

    public function handle(YtDlp $ytDlp): int
    {
        $version = $ytDlp->version();

        if ($version === null) {
            $this->error("yt-dlp was not found or failed to run (configured binary: {$ytDlp->binary()}).");
            $this->line('Install it with one of:');
            $this->line('  pipx install yt-dlp');
            $this->line('  python3 -m pip install -U "yt-dlp[default]"');
            $this->line('  Standalone binary: https://github.com/yt-dlp/yt-dlp#installation');

            return self::FAILURE;
        }

        $path   = $this->resolveBinary($ytDlp->binary());
        [$method, $command] = $this->detectInstallMethod($path);

        $this->table(['', ''], [
            ['Installed version', $version],
            ['Binary', $path ?? $ytDlp->binary()],
            ['Install method', $method],
            ['FFmpeg', $ytDlp->ffmpegVersion() ?? 'not found'],
        ]);

        if ($this->option('check-latest')) {
            $latest = $this->latestVersion();

            if ($latest === null) {
                $this->warn('Could not fetch the latest release from GitHub.');
            } elseif (ltrim($latest, 'v') === ltrim($version, 'v')) {
                $this->info("yt-dlp is up to date ({$latest}).");
            } else {
                $this->warn("A newer yt-dlp is available: {$latest}");
            }
        }

        $ageDays = $ytDlp->versionAgeInDays($version);
        if ($ageDays !== null && $ageDays > YtDlp::OUTDATED_AFTER_DAYS) {
            $this->newLine();
            $this->error(" yt-dlp {$version} is {$ageDays} days old. Most sites (YouTube, Facebook, Instagram…) will fail until it is updated. ");
        }

        $this->newLine();

        if ($method === 'apt / dpkg') {
            // apt only ships old releases, so "apt upgrade" does not help: replace it with the standalone binary
            $this->line('The apt package is always far behind. Replace it with the official standalone binary:');
            $this->line('  <comment>sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux -o /usr/local/bin/yt-dlp</comment>');
            $this->line('  <comment>sudo chmod a+rx /usr/local/bin/yt-dlp</comment>');
            $this->line('  <comment>sudo apt remove -y yt-dlp && hash -r</comment>');
            $this->line('Later updates: <comment>sudo yt-dlp -U</comment>');
        } else {
            $this->line('Sites change often; keep yt-dlp current. To update:');
            $this->line("  <comment>{$command}</comment>");
        }

        return self::SUCCESS;
    }

    private function resolveBinary(string $binary): ?string
    {
        if (str_contains($binary, DIRECTORY_SEPARATOR)) {
            return is_file($binary) ? (realpath($binary) ?: $binary) : null;
        }

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $binary;
            if (is_file($candidate) && is_executable($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return null;
    }

    /** @return array{0: string, 1: string} [method, update command] */
    private function detectInstallMethod(?string $path): array
    {
        if ($path === null) {
            return ['unknown', 'See https://github.com/yt-dlp/yt-dlp#update'];
        }

        if (str_contains($path, '/snap/')) {
            return ['snap', 'sudo snap refresh yt-dlp'];
        }

        if (preg_match('#/(Cellar|homebrew|linuxbrew)/#', $path)) {
            return ['Homebrew', 'brew upgrade yt-dlp'];
        }

        if (str_contains($path, '/pipx/')) {
            return ['pipx', 'pipx upgrade yt-dlp'];
        }

        if (str_starts_with($path, '/usr/bin/') || str_starts_with($path, '/bin/')) {
            $dpkg = rescue(fn () => Process::timeout(10)->run(['dpkg', '-S', $path]), null, false);
            if ($dpkg?->successful()) {
                return ['apt / dpkg', 'sudo apt update && sudo apt install --only-upgrade yt-dlp'];
            }
        }

        $head = (string) @file_get_contents($path, false, null, 0, 4);
        $size = (int) @filesize($path);

        // pip / pip --user entry points are tiny Python scripts
        if (str_starts_with($head, '#!') && $size < 64 * 1024) {
            return ['pip', 'python3 -m pip install -U "yt-dlp[default]"'];
        }

        // Standalone release (ELF / zipapp / exe) can update itself
        return ['standalone binary', escapeshellarg($path) . ' -U'];
    }

    private function latestVersion(): ?string
    {
        return rescue(function () {
            $response = Http::timeout(10)->acceptJson()->get(self::RELEASES_API);

            return $response->successful() ? ($response->json('tag_name') ?: null) : null;
        }, null, false);
    }
}
