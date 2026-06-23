<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UtImage extends Model
{
    use SoftDeletes;

    protected $table = 'ut_images';

    protected $fillable = [
        'uuid',
        'user_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected static function booting(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getPublicUrlAttribute(): string
    {
        return url("/open-images/{$this->uuid}");
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / (1024 ** $i), 2) . ' ' . ($units[$i] ?? 'B');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function storageExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function deleteFile(): void
    {
        if ($this->storageExists()) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }
}
