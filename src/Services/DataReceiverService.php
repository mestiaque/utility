<?php

namespace ME\Utility\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ME\Utility\Models\UdrDataLog;
use ME\Utility\Models\UdrUploadedFile;

class DataReceiverService
{
    public function process(Request $request): UdrDataLog
    {
        $contentType = $request->header('Content-Type', '');
        $baseType    = strtolower(explode(';', $contentType)[0]);
        $rawBody     = $request->getContent();

        $isJson   = str_contains($baseType, 'json');
        $isXml    = str_contains($baseType, 'xml') || str_contains($baseType, 'html');
        $isBinary = $this->isBinary($baseType);
        $isForm   = in_array($baseType, ['application/x-www-form-urlencoded', 'multipart/form-data'], true);

        $parsedBody = null;
        $formFields = null;

        if ($isJson && $rawBody) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE) $parsedBody = $decoded;
        }

        if ($isForm) {
            $formFields = $request->except(array_keys($request->allFiles())) ?: null;
        }

        $rawBodyStored = $isBinary
            ? '[binary — see uploaded files]'
            : (strlen($rawBody) > 10_000_000
                ? substr($rawBody, 0, 10_000_000) . "\n[truncated, original: " . strlen($rawBody) . ' bytes]'
                : ($rawBody ?: null));

        $log = UdrDataLog::create([
            'method'         => $request->method(),
            'full_url'       => $request->fullUrl(),
            'path'           => $request->path(),
            'host'           => $request->getHost(),
            'scheme'         => $request->getScheme(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'referer'        => $request->header('Referer'),
            'content_type'   => $contentType ?: null,
            'content_length' => $request->header('Content-Length') ? (int)$request->header('Content-Length') : null,
            'request_size'   => strlen($rawBody),
            'headers'        => $this->sanitizeHeaders($request->headers->all()),
            'query_params'   => $request->query() ?: null,
            'parsed_body'    => $parsedBody,
            'form_fields'    => $formFields,
            'raw_body'       => $rawBodyStored,
            'has_files'      => $request->allFiles() !== [],
            'is_json'        => $isJson,
            'is_xml'         => $isXml,
            'is_binary'      => $isBinary,
        ]);

        $fileInfo = [];
        foreach ($request->allFiles() as $fieldName => $files) {
            foreach ((array)$files as $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile) || !$file->isValid()) continue;
                $fileInfo[] = $this->storeFile($file, $log->id, $fieldName);
            }
        }

        if (!empty($fileInfo)) {
            $log->file_info = $fileInfo;
            $log->has_files = true;
            $log->save();
        }

        return $log;
    }

    private function storeFile(\Illuminate\Http\UploadedFile $file, int $logId, string $field): array
    {
        $ext        = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $mime       = $file->getMimeType() ?? $file->getClientMimeType();
        $folder     = 'udr/uploads/' . date('Y/m/d');
        $stored     = Str::uuid() . '.' . $ext;
        $path       = $folder . '/' . $stored;
        $hash       = hash_file('sha256', $file->getRealPath());

        Storage::disk('public')->putFileAs($folder, $file, $stored);

        $record = UdrUploadedFile::create([
            'data_log_id'   => $logId,
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $stored,
            'extension'     => $ext,
            'mime_type'     => $mime,
            'size'          => $file->getSize(),
            'sha256_hash'   => $hash,
            'disk'          => 'public',
            'storage_path'  => $path,
            'public_url'    => Storage::disk('public')->url($path),
            'field_name'    => $field,
        ]);

        return [
            'uuid'          => $record->uuid,
            'original_name' => $file->getClientOriginalName(),
            'extension'     => $ext,
            'mime_type'     => $mime,
            'size'          => $file->getSize(),
            'sha256_hash'   => $hash,
            'storage_path'  => $path,
        ];
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'cookie', 'set-cookie', 'x-api-key', 'x-auth-token'];
        return collect($headers)->mapWithKeys(fn($v, $k) =>
            [$k => in_array(strtolower($k), $sensitive, true) ? ['[redacted]'] : $v]
        )->all();
    }

    private function isBinary(string $type): bool
    {
        foreach (['image/', 'video/', 'audio/', 'application/octet-stream', 'application/zip', 'application/pdf'] as $p) {
            if (str_starts_with($type, $p)) return true;
        }
        return false;
    }
}
