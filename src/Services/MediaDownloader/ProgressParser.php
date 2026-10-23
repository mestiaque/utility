<?php

namespace ME\Utility\Services\MediaDownloader;

use Closure;

/**
 * Parses yt-dlp stdout (with --newline and our --progress-template) into progress callbacks.
 * Percent is only reported when yt-dlp knows the total size; otherwise it stays null so the
 * UI shows an indeterminate bar instead of an invented number.
 */
class ProgressParser
{
    private string $buffer = '';
    private int $part = 0;
    private ?string $stage = null;
    private ?array $pending = null;
    private float $lastEmit = 0.0;

    /** @var string[] */
    private array $tail = [];

    /**
     * @param Closure(?float, ?int, ?int, ?string): void $onProgress
     */
    public function __construct(
        private readonly Closure $onProgress,
        private readonly float $minInterval = 1.0,
    ) {
    }

    public function feed(string $chunk): void
    {
        if ($chunk === '') {
            return;
        }

        $this->buffer .= $chunk;

        while (($pos = strpos($this->buffer, "\n")) !== false) {
            $line         = rtrim(substr($this->buffer, 0, $pos), "\r");
            $this->buffer = substr($this->buffer, $pos + 1);
            $this->line($line);
        }
    }

    /** Emit whatever is still pending (called once the process ends). */
    public function flush(): void
    {
        if ($this->buffer !== '') {
            $this->line($this->buffer);
            $this->buffer = '';
        }

        if ($this->pending !== null) {
            $this->emit(...$this->pending);
        }
    }

    /** True while yt-dlp / FFmpeg merge, convert or fix up the file (no byte progress then). */
    public function isPostProcessing(): bool
    {
        return in_array($this->stage, ['Merging audio & video', 'Converting audio', 'Finalizing'], true);
    }

    public function tail(): string
    {
        return implode("\n", $this->tail);
    }

    private function line(string $line): void
    {
        if ($line === '') {
            return;
        }

        if (str_starts_with($line, YtDlp::PROGRESS_PREFIX)) {
            $this->progressLine(substr($line, strlen(YtDlp::PROGRESS_PREFIX)));

            return;
        }

        $this->tail[] = $line;
        $this->tail   = array_slice($this->tail, -20);

        $stage = match (true) {
            str_contains($line, '[download] Destination:') => $this->nextPart(),
            str_starts_with($line, '[Merger]')              => 'Merging audio & video',
            str_starts_with($line, '[ExtractAudio]')        => 'Converting audio',
            str_starts_with($line, '[Fixup')                => 'Finalizing',
            default                                         => null,
        };

        if ($stage !== null && $stage !== $this->stage) {
            $this->stage = $stage;
            // Post-processing has no reliable percentage
            $percent = str_starts_with($stage, 'Downloading') ? 0.0 : null;
            $this->emit($percent, null, null, $stage, force: true);
        }
    }

    private function progressLine(string $payload): void
    {
        [$downloaded, $total, $estimate, $speed, $eta] = array_pad(explode('|', $payload), 5, 'NA');

        $downloaded = self::number($downloaded);
        $size       = self::number($total) ?? self::number($estimate);

        $percent = $downloaded !== null && $size !== null && $size > 0
            ? round(min(100, $downloaded / $size * 100), 2)
            : null;

        $speed = self::number($speed);
        $eta   = self::number($eta);

        $this->emit(
            $percent,
            $speed !== null ? (int) $speed : null,
            $eta !== null ? (int) $eta : null,
            $this->stage ?? $this->nextPart(),
        );
    }

    private function nextPart(): string
    {
        $this->part++;

        return $this->part > 1 ? "Downloading (part {$this->part})" : 'Downloading';
    }

    private function emit(?float $percent, ?int $speed, ?int $eta, ?string $stage, bool $force = false): void
    {
        $this->stage ??= $stage;
        $now = microtime(true);

        if (! $force && $now - $this->lastEmit < $this->minInterval) {
            $this->pending = [$percent, $speed, $eta, $stage];

            return;
        }

        $this->pending  = null;
        $this->lastEmit = $now;
        ($this->onProgress)($percent, $speed, $eta, $stage);
    }

    private static function number(string $value): ?float
    {
        $value = trim($value);

        return is_numeric($value) ? (float) $value : null;
    }
}
