I already have an existing Laravel utility package.

DO NOT create a new Laravel application.

Your task is to add a complete **Media Downloader feature** inside my existing utility package while preserving the package's current architecture, coding style, namespace, service provider, configuration, routes, views, and existing features.

## IMPORTANT

Before writing or modifying code:

1. Inspect the entire existing package.
2. Understand its current architecture.
3. Identify:

   * composer.json
   * ServiceProvider
   * existing services
   * controllers
   * routes
   * views
   * config files
   * assets
   * helper classes
   * existing conventions
4. Follow the existing package structure instead of creating an unrelated architecture.
5. Do not break any existing feature.
6. Do not rename or remove existing classes/methods unless absolutely necessary.
7. Reuse existing utilities/components wherever possible.

If the package already has a dashboard/menu/system for utilities, integrate the new feature into that system.

---

# FEATURE: MEDIA DOWNLOADER

Add a new utility called:

**Media Downloader**

The purpose is to allow the user to paste a publicly accessible media URL and download the available media through the package's web interface.

Example supported URLs may include:

* YouTube
* TikTok
* Facebook
* Instagram
* X/Twitter
* Pinterest

Use `yt-dlp` as the extraction/download engine.

Do NOT use a paid third-party downloader API.

Use FFmpeg when required for merging video/audio or audio conversion.

Only support publicly accessible media that the user is authorized to download and that is permitted by the relevant platform terms and applicable copyright rules.

Do NOT implement:

* DRM bypass
* private-content bypass
* login bypass
* CAPTCHA bypass
* paywall bypass
* cookie/session theft
* authentication bypass
* anti-bot evasion

---

# PACKAGE INTEGRATION

The feature must be a proper part of my existing utility package.

For example, if my package currently has something like:

```text
src/
    Http/
    Services/
    Models/
    ...
    MyPackageServiceProvider.php
```

then integrate the new feature according to the existing structure.

Do NOT blindly create a completely new structure.

If the package already has a service registration mechanism, use it.

If the package already has routes, extend them.

If the package already has navigation/menu registration, add:

```text
Media Downloader
```

there.

If the package already has a common layout, use it.

If the package already has reusable Blade components, use them.

---

# USER INTERFACE

Create a clean responsive Media Downloader page.

Main interface:

```text
Media Downloader

Paste public media URL

[ https://example.com/...              ]

              [ Analyze ]
```

After successful analysis:

```text
Title:
Video title

Platform:
YouTube

Thumbnail:
[ image ]

Duration:
05:42

Uploader:
Example Channel

Available Formats:

Video
--------------------------------------------
Quality    Format    Size       Action
1080p      MP4       25 MB      Download
720p       MP4       12 MB      Download
480p       MP4        7 MB      Download

Audio
--------------------------------------------
Best       M4A        4 MB      Download
MP3        MP3        5 MB      Download
```

Make the UI match the existing package's design.

Do NOT introduce a completely different visual style if the package already has a UI system.

---

# URL ANALYSIS

Create an analysis flow.

The user submits:

```text
url
```

Validate it.

Only allow:

```text
http://
https://
```

Reject dangerous protocols and local/private network targets.

Prevent SSRF.

Use yt-dlp to retrieve metadata.

Conceptually:

```bash
yt-dlp --dump-single-json --no-playlist "URL"
```

BUT:

Never concatenate raw user input into shell commands.

Use Laravel's Process API or another safe process execution mechanism with proper argument handling.

---

# MEDIA INFORMATION

After analysis, normalize yt-dlp output into a clean structure.

Display:

* title
* thumbnail
* platform
* uploader
* duration
* description when available
* available formats
* resolution
* FPS
* extension
* video codec
* audio codec
* approximate filesize

Do not expose raw yt-dlp JSON directly to the user.

Create a dedicated DTO/value object if the existing package architecture supports it.

---

# FORMAT SELECTION

Allow users to select available formats.

Video options:

* Best
* 2160p if available
* 1440p if available
* 1080p
* 720p
* 480p
* 360p

Audio options:

* Best audio
* M4A if available
* MP3 conversion

Only display formats that actually exist.

If video and audio are separate streams, use FFmpeg to merge them.

Example format selection:

```text
bestvideo[height<=1080]+bestaudio/best
```

Build selectors dynamically.

---

# DOWNLOAD

When the user selects a format:

```text
Download
```

the package should:

1. Validate the URL again.
2. Validate the selected format.
3. Create a temporary download location.
4. Run yt-dlp safely.
5. Use FFmpeg when required.
6. Verify the output file.
7. Return the file to the browser.
8. Clean up temporary files.

Do not permanently store media unless explicitly configured.

---

# QUEUE SUPPORT

If the existing package already supports Laravel queues, integrate with it.

If not, design the feature so queue support can be enabled without rewriting the entire feature.

Prefer:

```text
MediaDownloadJob
```

for longer downloads.

The job should contain only the necessary data, such as:

```text
url
format
download_id
output_type
```

Do not serialize huge media files into the queue.

---

# PROGRESS

If practical with the existing package architecture, show download progress.

Example:

```text
Downloading...

42%

Speed:
2.4 MiB/s

ETA:
00:12
```

Use polling if necessary.

Example endpoint:

```text
GET /media-downloader/downloads/{id}/status
```

If yt-dlp does not provide reliable progress information, show an indeterminate progress indicator instead of inventing a percentage.

---

# DOWNLOAD HISTORY

If the existing package has database support, add a download history model/table.

Suggested model:

```text
MediaDownload
```

Suggested fields:

```text
id
url
platform
title
format
extension
status
progress
filesize
error_message
started_at
completed_at
created_at
updated_at
```

Statuses:

```text
pending
analyzing
downloading
completed
failed
cancelled
```

If the package intentionally does not use a database, do not force a database architecture onto it. Adapt to the package's existing design.

---

# CONFIGURATION

Add configuration only through the package's existing configuration system.

Possible configuration:

```php
'media_downloader' => [

    'yt_dlp_binary' => env('YT_DLP_BINARY', 'yt-dlp'),

    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),

    'timeout' => env('YT_DLP_TIMEOUT', 300),

    'temporary_directory' => storage_path('app/media-temp'),

    'max_file_size' => 2 * 1024 * 1024 * 1024,

],
```

Do NOT duplicate configuration if the package already has an appropriate config system.

---

# COMMANDS

Add package Artisan commands if appropriate.

For example:

```bash
php artisan utility:media-check
```

It should check:

```text
Laravel       OK
PHP           OK
yt-dlp        OK
FFmpeg        OK
Storage       OK
```

If the package already uses a different command naming convention, follow that convention.

---

# yt-dlp UPDATE

Provide an optional package command to display/check the yt-dlp version.

Example:

```bash
php artisan utility:media-version
```

Do not assume that yt-dlp can always be automatically updated. Detect the installation method and provide appropriate instructions.

---

# SECURITY

Security is extremely important.

Implement:

### URL validation

Reject:

```text
file://
ftp://
php://
data://
localhost
127.0.0.1
0.0.0.0
private IP addresses
```

Prevent SSRF.

### Process execution

Never do:

```php
exec("yt-dlp " . $url);
```

Never do:

```php
shell_exec("yt-dlp " . $url);
```

Use Laravel Process API or safe argument-based execution.

### File names

Never use the raw URL as a filename.

Generate safe filenames.

### File storage

Use temporary directories.

Automatically remove temporary files.

### Rate limiting

If the host application/package supports Laravel rate limiting, use it for:

```text
analyze
download
```

---

# ERROR HANDLING

Handle:

* invalid URL
* unsupported URL
* private media
* login-required media
* deleted media
* unavailable media
* extraction failure
* FFmpeg failure
* timeout
* network error
* disk-space error
* permission error
* unsupported format

Show friendly messages.

Example:

```text
Unable to download this media.

The content may be unavailable, private,
unsupported, or temporarily inaccessible.
```

Log technical details through the package/application's existing logging mechanism.

Do not expose stack traces to users.

---

# CLEANUP

Temporary files must always be cleaned up.

Use:

```text
try/finally
```

or the package's existing cleanup mechanism.

If appropriate, add a scheduled cleanup command.

Do not leave downloaded media or partial files indefinitely.

---

# TESTING

Add tests using the package's existing testing structure.

Test:

### URL validation

* valid HTTPS
* invalid URL
* localhost
* 127.0.0.1
* private IP
* invalid protocol

### Controller

* validation
* successful analysis
* failed analysis

### Service

Mock the yt-dlp process.

Do NOT download real external videos during normal automated tests.

### Download lifecycle

Test:

```text
pending
downloading
completed
failed
cancelled
```

---

# DOCUMENTATION

Update the package README/documentation.

Explain:

1. Feature overview
2. Installation requirements
3. yt-dlp installation
4. FFmpeg installation
5. Configuration
6. Routes
7. Usage
8. Artisan commands
9. Queue configuration if applicable
10. Troubleshooting
11. Security considerations

---

# IMPORTANT DEVELOPMENT RULE

Before changing anything, inspect my existing package and understand it.

Do NOT assume the package has the architecture described above.

Adapt everything to the existing package.

For example:

If the package already has:

```text
src/Http/Controllers
```

use it.

If it has:

```text
src/Services
```

use it.

If it has:

```text
resources/views
```

use it.

If it has:

```text
routes/web.php
```

extend it.

If it has an existing ServiceProvider, modify that provider instead of creating a duplicate provider.

If it has existing menu/sidebar registration, integrate Media Downloader into it.

If it has existing permission/authorization logic, use that.

If it has existing layouts/components, reuse them.

---

# CODE QUALITY

Follow:

* PSR-12
* Laravel conventions
* Dependency injection
* SOLID principles where appropriate
* small focused classes
* typed properties
* return types
* proper exception handling
* reusable services
* clean naming

Avoid:

* duplicated logic
* huge controllers
* raw shell commands
* hard-coded paths
* hard-coded binaries
* hard-coded platform-specific scraping logic

---

# BACKWARD COMPATIBILITY

This is an existing package.

Therefore:

**Existing features must continue working exactly as before.**

Do not:

* rename the package
* change the namespace unnecessarily
* remove existing routes
* remove existing classes
* remove existing config keys
* replace the existing ServiceProvider
* rewrite unrelated code
* change existing UI unnecessarily

Only add or minimally modify what is required for the Media Downloader feature.

---

# FINAL REQUIREMENT

After implementation, provide:

1. List of files created
2. List of files modified
3. Explanation of how the feature works
4. Required composer changes
5. Required `.env` variables
6. Required migrations
7. Required Artisan commands
8. yt-dlp installation command
9. FFmpeg installation command
10. How to test the feature locally

Most importantly:

**First inspect the existing package. Then implement the feature according to its actual architecture. Do not generate a generic standalone Laravel application.**

