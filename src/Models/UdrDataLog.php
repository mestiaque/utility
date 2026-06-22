<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UdrDataLog extends Model
{
    protected $table = 'udr_data_logs';

    protected $fillable = [
        'uuid', 'method', 'full_url', 'path', 'host', 'scheme',
        'ip_address', 'user_agent', 'referer', 'content_type',
        'content_length', 'request_size', 'headers', 'query_params',
        'parsed_body', 'form_fields', 'file_info', 'raw_body',
        'has_files', 'is_json', 'is_xml', 'is_binary', 'received_at',
    ];

    protected $casts = [
        'headers'        => 'array',
        'query_params'   => 'array',
        'parsed_body'    => 'array',
        'form_fields'    => 'array',
        'file_info'      => 'array',
        'has_files'      => 'boolean',
        'is_json'        => 'boolean',
        'is_xml'         => 'boolean',
        'is_binary'      => 'boolean',
        'received_at'    => 'datetime',
        'content_length' => 'integer',
        'request_size'   => 'integer',
    ];

    protected static function booting(): void
    {
        static::creating(function (self $model) {
            $model->uuid        ??= (string) Str::uuid();
            $model->received_at ??= now();
        });
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(UdrUploadedFile::class, 'data_log_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->request_size ?? 0;
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / (1024 ** $i), 2) . ' ' . ($units[$i] ?? 'B');
    }

    public function getShortContentTypeAttribute(): string
    {
        return $this->content_type ? explode(';', $this->content_type)[0] : 'unknown';
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['method']))
            $query->where('method', strtoupper($filters['method']));

        if (!empty($filters['ip']))
            $query->where('ip_address', 'like', '%'.$filters['ip'].'%');

        if (!empty($filters['content_type']))
            $query->where('content_type', 'like', '%'.$filters['content_type'].'%');

        if (!empty($filters['keyword']))
            $query->where(fn($q) => $q
                ->where('ip_address', 'like', '%'.$filters['keyword'].'%')
                ->orWhere('path', 'like', '%'.$filters['keyword'].'%')
                ->orWhere('user_agent', 'like', '%'.$filters['keyword'].'%')
                ->orWhere('raw_body', 'like', '%'.$filters['keyword'].'%')
            );

        if (!empty($filters['date_from']))
            $query->whereDate('received_at', '>=', $filters['date_from']);

        if (!empty($filters['date_to']))
            $query->whereDate('received_at', '<=', $filters['date_to']);

        if (isset($filters['has_files']) && $filters['has_files'] !== '')
            $query->where('has_files', (bool)$filters['has_files']);

        return $query;
    }
}
