<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UdrUploadedFile extends Model
{
    protected $table = 'udr_uploaded_files';

    protected $fillable = [
        'uuid', 'data_log_id', 'original_name', 'stored_name',
        'extension', 'mime_type', 'size', 'sha256_hash',
        'disk', 'storage_path', 'public_url', 'field_name',
    ];

    protected $casts = ['size' => 'integer'];

    protected static function booting(): void
    {
        static::creating(fn(self $m) => $m->uuid ??= (string) Str::uuid());
    }

    public function dataLog(): BelongsTo
    {
        return $this->belongsTo(UdrDataLog::class, 'data_log_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / (1024 ** $i), 2) . ' ' . ($units[$i] ?? 'B');
    }

    public function getIsImageAttribute(): bool  { return str_starts_with($this->mime_type ?? '', 'image/'); }
    public function getIsVideoAttribute(): bool   { return str_starts_with($this->mime_type ?? '', 'video/'); }
    public function getIsAudioAttribute(): bool   { return str_starts_with($this->mime_type ?? '', 'audio/'); }
    public function getIsPdfAttribute(): bool     { return $this->mime_type === 'application/pdf'; }

    public function storageExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->storage_path);
    }
}
