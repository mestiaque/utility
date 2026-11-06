<?php
return [
    'key' => 'value',

    // ── Media Downloader ──────────────────────────────────────────────
    'media_downloader' => [

        // Binaries (absolute path or name resolvable through $PATH)
        'yt_dlp_binary' => env('YT_DLP_BINARY', 'yt-dlp'),
        'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        // Optional image engine (TikTok photo posts, carousels…); without it only video/audio
        'gallery_dl_binary' => env('GALLERY_DL_BINARY', 'gallery-dl'),
        // Where "Server setup" (or utility:media-install) puts yt-dlp / FFmpeg when the server
        // has none, e.g. cPanel shared hosting. Used automatically while the binaries above are bare names.
        'tools_directory' => env('MEDIA_DOWNLOADER_TOOLS_DIR', storage_path('app/media-tools')),

        // Seconds: link analysis limit, a download's absolute maximum, and how long a download
        // may show no progress before it is treated as stuck (long videos are fine while they move)
        'analyze_timeout' => (int) env('YT_DLP_ANALYZE_TIMEOUT', 60),
        'timeout'         => (int) env('YT_DLP_TIMEOUT', 10800),
        'stall_timeout'   => (int) env('YT_DLP_STALL_TIMEOUT', 120),

        // Debugging a site yt-dlp can no longer read: analysis saves the fetched pages here.
        // Pages from a logged-in session contain personal data — leave unset normally.
        'debug_pages_directory' => env('MEDIA_DOWNLOADER_DEBUG_PAGES'),

        // Parallel fragment downloads for HLS / DASH streams (much faster for long videos)
        'concurrent_fragments' => (int) env('MEDIA_DOWNLOADER_FRAGMENTS', 4),

        // Working directory for in-progress / not-yet-fetched downloads
        'temporary_directory' => env('MEDIA_DOWNLOADER_TEMP', storage_path('app/media-temp')),

        // Bytes — refuse anything larger (passed to yt-dlp --max-filesize too)
        'max_file_size' => (int) env('MEDIA_DOWNLOADER_MAX_SIZE', 2 * 1024 * 1024 * 1024),

        // Bytes of free disk space required before a download starts
        'min_free_space' => (int) env('MEDIA_DOWNLOADER_MIN_FREE', 512 * 1024 * 1024),

        // Minutes a finished file waits to be fetched before cleanup removes it
        'file_lifetime' => (int) env('MEDIA_DOWNLOADER_FILE_LIFETIME', 30),

        // Keep the file on disk after it has been sent to the browser
        'keep_files' => (bool) env('MEDIA_DOWNLOADER_KEEP_FILES', false),

        // Encrypted per-user login cookies (cookies.txt) for private media the user's own
        // account can access. Keep outside public/; must be writable by the web user.
        'cookies_directory' => env('MEDIA_DOWNLOADER_COOKIES_DIR', storage_path('app/private/media-downloader-cookies')),

        // Bulk: max items read from a playlist / channel / profile, and max per bulk download
        'max_batch_items' => (int) env('MEDIA_DOWNLOADER_MAX_BATCH', 200),

        // Bulk politeness: seconds to wait between items, and after this many items in a row
        // are refused by the site (HTTP 403/429) the rest of the batch is skipped, not hammered.
        'bulk_delay'          => (int) env('MEDIA_DOWNLOADER_BULK_DELAY', 3),
        'bulk_block_threshold' => (int) env('MEDIA_DOWNLOADER_BULK_BLOCK_THRESHOLD', 3),

        // Minutes an analysis result is cached (download re-uses it)
        'analysis_cache_minutes' => 30,

        // Optional host allow-list, e.g. ['youtube.com', 'tiktok.com'].
        // Empty = any public host. Sub-domains of a listed host are allowed.
        'allowed_hosts' => array_filter(explode(',', (string) env('MEDIA_DOWNLOADER_ALLOWED_HOSTS', ''))),

        // Queue: false runs the download after the HTTP response is sent.
        // "media-downloader" is registered by the package (database driver, long retry_after).
        'queue' => [
            'enabled'    => (bool) env('MEDIA_DOWNLOADER_QUEUE', false),
            'connection' => env('MEDIA_DOWNLOADER_QUEUE_CONNECTION', 'media-downloader'),
            'name'       => env('MEDIA_DOWNLOADER_QUEUE_NAME', 'media'),
        ],

        // Laravel throttle middleware values ("attempts,minutes")
        'throttle' => [
            'analyze'  => env('MEDIA_DOWNLOADER_THROTTLE_ANALYZE', '10,1'),
            'download' => env('MEDIA_DOWNLOADER_THROTTLE_DOWNLOAD', '5,1'),
        ],

        // Register "utility:media-cleanup" on the scheduler (hourly)
        'schedule_cleanup' => true,
    ],
];
