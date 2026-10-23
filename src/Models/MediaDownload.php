<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ME\Utility\Services\MediaDownloader\Data\MediaFormatOption;

class MediaDownload extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_ANALYZING   = 'analyzing';
    public const STATUS_DOWNLOADING = 'downloading';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_FAILED      = 'failed';
    public const STATUS_CANCELLED   = 'cancelled';

    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_ANALYZING, self::STATUS_DOWNLOADING];

    protected $table = 'ut_media_downloads';

    protected $fillable = [
        'uuid',
        'user_id',
        'batch_id',
        'batch_title',
        'url',
        'platform',
        'title',
        'format',
        'output_type',
        'extension',
        'status',
        'stage',
        'progress',
        'speed',
        'eta',
        'filesize',
        'file_path',
        'file_name',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $hidden = ['file_path'];

    protected $casts = [
        'progress'     => 'float',
        'speed'        => 'integer',
        'eta'          => 'integer',
        'filesize'     => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected static function booting(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeOwnedBy($query, $userId)
    {
        return $userId === null ? $query->whereNull('user_id') : $query->where('user_id', $userId);
    }

    // ── State ──────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function hasFile(): bool
    {
        return $this->isCompleted() && $this->file_path !== null && is_file($this->file_path);
    }

    // ── Lifecycle transitions ──────────────────────────────────────────────────
    // Each transition is a conditional UPDATE so a cancel from the browser can never be
    // overwritten by a worker that finishes a moment later (and vice versa).

    public function markAnalyzing(): bool
    {
        return $this->transition([self::STATUS_PENDING], [
            'status' => self::STATUS_ANALYZING,
            'stage'  => 'Analyzing',
        ]);
    }

    public function markDownloading(): bool
    {
        return $this->transition([self::STATUS_PENDING, self::STATUS_ANALYZING], [
            'status'     => self::STATUS_DOWNLOADING,
            'stage'      => 'Starting download',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(string $filePath, string $fileName, int $filesize, string $extension): bool
    {
        return $this->transition([self::STATUS_DOWNLOADING], [
            'status'       => self::STATUS_COMPLETED,
            'stage'        => 'Ready',
            'progress'     => 100,
            'speed'        => null,
            'eta'          => null,
            'file_path'    => $filePath,
            'file_name'    => $fileName,
            'filesize'     => $filesize,
            'extension'    => $extension,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $message): bool
    {
        return $this->transition(self::ACTIVE_STATUSES, [
            'status'        => self::STATUS_FAILED,
            'stage'         => null,
            'speed'         => null,
            'eta'           => null,
            'error_message' => Str::limit($message, 1000),
            'completed_at'  => now(),
        ]);
    }

    public function markCancelled(): bool
    {
        return $this->transition(self::ACTIVE_STATUSES, [
            'status'       => self::STATUS_CANCELLED,
            'stage'        => null,
            'speed'        => null,
            'eta'          => null,
            'completed_at' => now(),
        ]);
    }

    /** Progress writes only apply while downloading; returns false once cancelled. */
    public function updateProgress(?float $percent, ?int $speed, ?int $eta, ?string $stage = null): bool
    {
        $data = ['progress' => $percent, 'speed' => $speed, 'eta' => $eta];
        if ($stage !== null) {
            $data['stage'] = $stage;
        }

        return $this->transition([self::STATUS_DOWNLOADING], $data);
    }

    public function forgetFile(): void
    {
        $this->forceFill(['file_path' => null])->save();
    }

    private function transition(array $from, array $data): bool
    {
        $data['updated_at'] = now();

        $updated = static::query()
            ->whereKey($this->getKey())
            ->whereIn('status', $from)
            ->update($data);

        if ($updated === 0) {
            $this->refresh();

            return false;
        }

        $this->forceFill($data)->syncOriginal();

        return true;
    }

    // ── Presentation ───────────────────────────────────────────────────────────

    public function getFormattedSizeAttribute(): ?string
    {
        return $this->filesize ? MediaFormatOption::humanBytes($this->filesize) : null;
    }

    public function toStatusArray(): array
    {
        return [
            'id'            => $this->uuid,
            'batch_id'      => $this->batch_id,
            'format'        => $this->format,
            'status'        => $this->status,
            'stage'         => $this->stage,
            'progress'      => $this->progress,
            'speed'         => $this->speed ? MediaFormatOption::humanBytes($this->speed) . '/s' : null,
            'eta'           => $this->eta !== null ? gmdate($this->eta >= 3600 ? 'H:i:s' : 'i:s', $this->eta) : null,
            'filesize'      => $this->formatted_size,
            'title'         => $this->title,
            'error_message' => $this->error_message,
            'file_url'      => $this->hasFile() ? route('ut.media-downloader.file', $this->uuid) : null,
        ];
    }
}
