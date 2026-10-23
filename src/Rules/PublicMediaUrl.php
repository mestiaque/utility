<?php

namespace ME\Utility\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use ME\Utility\Services\MediaDownloader\UrlValidator;

/**
 * Validation rule: http(s) URL that resolves only to public addresses (SSRF guard).
 */
class PublicMediaUrl implements ValidationRule
{
    public function __construct(private readonly UrlValidator $validator)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(MediaDownloaderException::because(MediaDownloaderException::INVALID_URL)->userMessage());

            return;
        }

        try {
            $this->validator->validate($value);
        } catch (MediaDownloaderException $e) {
            $fail($e->userMessage());
        }
    }
}
