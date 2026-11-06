# Utility Package

Auto-generated Laravel package.

---

## Media Downloader

Paste a media URL (YouTube, TikTok, Facebook, Instagram, X/Twitter, Pinterest and the
[many other sites yt-dlp supports](https://github.com/yt-dlp/yt-dlp/blob/master/supportedsites.md)),
see its title, thumbnail, duration, uploader and the formats that actually exist, then download a
video quality or an audio track (best, M4A, or MP3).

Extraction and downloading are handled by the open-source [yt-dlp](https://github.com/yt-dlp/yt-dlp),
and FFmpeg merges separate video and audio streams and converts to MP3. No paid third-party API is used.

> Only download media you are authorised to download and that the platform's terms and copyright law
> allow. Private media is supported **only through your own login cookies**, meaning content your own account
> can already open in a browser. DRM-protected streams, paywalls and CAPTCHA/anti-bot circumvention
> are not supported.

### 1. How it works

```
Browser ──POST /analyze──► UrlValidator   (SSRF guard) ─► yt-dlp --dump-single-json ─► FormatOptionBuilder
        ◄── normalized MediaInfo (title, platform, formats…; no raw JSON, no selectors) ──┘

Browser ──POST /downloads {url, format key}──► validate again ─► MediaDownload row (pending)
                                                                  └► MediaDownloadJob (queue or after-response)
Job: analyzing → downloading (yt-dlp + FFmpeg, progress written to the row) → completed | failed | cancelled
Browser ──GET /downloads/{id}/status (polling every 1.5 s)──► progress %, speed, ETA
Browser ──GET /downloads/{id}/file──► file streamed, then its temp directory is deleted
```

| Piece | Location |
|---|---|
| Controller | `src/Http/Controllers/MediaDownloaderController.php` |
| Services | `src/Services/MediaDownloader/` (`MediaDownloadService`, `YtDlp`, `UrlValidator`, `FormatOptionBuilder`, `ProgressParser`, `ErrorClassifier`) |
| DTOs | `src/Services/MediaDownloader/Data/` (`MediaInfo`, `MediaFormatOption`) |
| Job | `src/Jobs/MediaDownloadJob.php` (IDs and URL only, never media bytes) |
| Model / table | `src/Models/MediaDownload.php` / `ut_media_downloads` |
| View | `src/resources/views/media-downloader/index.blade.php` (uses `me::master`) |
| Commands | `src/Console/Commands/Media*Command.php` |
| Sidebar | `src/Config/sidebar.php` → **Media Downloader** |

Quality options are built from the formats yt-dlp reports: **Best**, then 2160p / 1440p / 1080p /
720p / 480p / 360p, each shown only if it exists (portrait videos are grouped by their short side).
Each option's yt-dlp selector is pinned to the analysed format id and falls back to generic
selectors, for example `137+bestaudio[ext=m4a]/bestvideo[height<=?1080]+bestaudio/best[height<=?1080]/best`.
Selectors stay on the server. The browser only sends an option key such as `video-1080` or `audio-mp3`.

### 2. Requirements

- PHP 8.2+, Laravel 11 or 12 (uses the `Illuminate\Support\Facades\Process` API)
- `yt-dlp` (keep it current, because sites change often)
- `ffmpeg` (needed for merging video with audio, MP3, and audio extraction)
- A writable temporary directory (default `storage/app/media-temp`)
- Optional: `ext-pcntl` so cancel and timeout can stop yt-dlp cleanly

### cPanel / shared hosting (no root, no terminal)

Open the Media Downloader page → **Server setup** → **Install / update tools**. The official
yt-dlp build (the "onedir" one, which also works where `/tmp` is mounted `noexec`) and a static
FFmpeg build are downloaded into `storage/app/media-tools` and used automatically — no `.env`
change is needed while `YT_DLP_BINARY` / `FFMPEG_BINARY` are left at their default names.
The same dialog lists what the host blocks (e.g. `proc_open` in `disable_functions`, time limits).
Click the button again later to update yt-dlp. With SSH: `php artisan utility:media-install`.

Shared hosts still limit CPU time, memory and disk, and big sites often block their IPs, so long
or bulk downloads may stop; a small VPS avoids all of that.

### 3. Installing yt-dlp

Distribution packages (e.g. Ubuntu's `apt install yt-dlp`) are usually too old to work with YouTube.
Prefer one of these:

```bash
# pipx (recommended on Linux / macOS)
sudo apt install pipx && pipx install yt-dlp        # update: pipx upgrade yt-dlp

# pip
python3 -m pip install -U "yt-dlp[default]"

# Standalone binary (self-updating with `yt-dlp -U`)
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp

# macOS
brew install yt-dlp
```

Recent yt-dlp versions need a JavaScript runtime ([deno](https://deno.com)) for full YouTube support.
See the [yt-dlp EJS wiki](https://github.com/yt-dlp/yt-dlp/wiki/EJS).

Make sure the **web server / queue worker user** can run it (`sudo -u www-data yt-dlp --version`).
If it is not on that user's `PATH`, set `YT_DLP_BINARY` to the absolute path.

### 4. Installing FFmpeg

```bash
sudo apt install ffmpeg          # Debian / Ubuntu
sudo dnf install ffmpeg          # Fedora (RPM Fusion)
brew install ffmpeg              # macOS
```

### 5. Configuration

The settings live under the `media_downloader` key of the package config (`src/Config/config.php`,
read as `config('utility.media_downloader.*')`). They can all be set from `.env`:

| `.env` variable | Default | Purpose |
|---|---|---|
| `YT_DLP_BINARY` | `yt-dlp` | Name on `PATH` or absolute path |
| `GALLERY_DL_BINARY` | `gallery-dl` | Optional image engine (photo posts, carousels) |
| `FFMPEG_BINARY` | `ffmpeg` | Name on `PATH` or absolute path (an absolute path is passed to `--ffmpeg-location`) |
| `YT_DLP_ANALYZE_TIMEOUT` | `60` | Seconds allowed for metadata extraction |
| `YT_DLP_TIMEOUT` | `10800` (3 h) | Absolute maximum for one download |
| `YT_DLP_STALL_TIMEOUT` | `120` | A download is stopped only after this many seconds **without progress** (long videos run as long as they move; merging/converting is not counted) |
| `MEDIA_DOWNLOADER_FRAGMENTS` | `4` | Fragments fetched in parallel for HLS/DASH streams (`1` = one at a time) |
| `MEDIA_DOWNLOADER_TEMP` | `storage/app/media-temp` | Temporary working directory |
| `MEDIA_DOWNLOADER_MAX_SIZE` | `2147483648` (2 GB) | Max file size in bytes (also passed to yt-dlp) | 10GB = 10737418240
| `MEDIA_DOWNLOADER_MIN_FREE` | `536870912` (512 MB) | Required free disk space before a download starts |
| `MEDIA_DOWNLOADER_FILE_LIFETIME` | `30` | Minutes a finished file waits to be fetched (`0` = never expire) |
| `MEDIA_DOWNLOADER_KEEP_FILES` | `false` | Keep files after they are sent to the browser |
| `MEDIA_DOWNLOADER_MAX_BATCH` | `200` | Max items read from a list and per bulk download |
| `MEDIA_DOWNLOADER_COOKIES_DIR` | `storage/app/private/media-downloader-cookies` | Encrypted per-user login cookies |
| `MEDIA_DOWNLOADER_ALLOWED_HOSTS` | *(empty = any public host)* | Comma-separated allow-list, e.g. `youtube.com,youtu.be,tiktok.com` |
| `MEDIA_DOWNLOADER_QUEUE` | `false` | Run downloads on a queue worker |
| `MEDIA_DOWNLOADER_QUEUE_CONNECTION` | app default | Queue connection |
| `MEDIA_DOWNLOADER_QUEUE_NAME` | `default` | Queue name |
| `MEDIA_DOWNLOADER_THROTTLE_ANALYZE` | `10,1` | Rate limit (attempts, minutes) for analyze |
| `MEDIA_DOWNLOADER_THROTTLE_DOWNLOAD` | `5,1` | Rate limit for starting downloads |

After changing `.env` in production run `php artisan config:clear` (or `config:cache`).

### 6. Routes

All routes are inside the existing `utility` group (`web`, `auth`, locale, activity log) and are named
`ut.media-downloader.*`:

| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/utility/media-downloader` | `ut.media-downloader.index` | Page and download history |
| POST | `/utility/media-downloader/analyze` | `ut.media-downloader.analyze` | Analyze a URL (throttled) |
| POST | `/utility/media-downloader/downloads` | `ut.media-downloader.download` | Start a download (throttled) |
| GET | `/utility/media-downloader/downloads/{id}/status` | `ut.media-downloader.status` | Progress polling |
| GET | `/utility/media-downloader/downloads/{id}/file` | `ut.media-downloader.file` | Fetch the finished file |
| POST | `/utility/media-downloader/downloads/{id}/cancel` | `ut.media-downloader.cancel` | Cancel |
| DELETE | `/utility/media-downloader/downloads/{id}` | `ut.media-downloader.destroy` | Remove from history |
| POST | `/utility/media-downloader/batches` | `ut.media-downloader.batch.start` | Start a bulk download (throttled) |
| GET | `/utility/media-downloader/batches/{batch}/status` | `ut.media-downloader.batch.status` | Bulk progress polling |
| POST | `/utility/media-downloader/batches/{batch}/cancel` | `ut.media-downloader.batch.cancel` | Cancel remaining items |
| GET | `/utility/media-downloader/batches/{batch}/zip` | `ut.media-downloader.batch.zip` | All finished files as one ZIP |
| POST / DELETE | `/utility/media-downloader/cookies` | `ut.media-downloader.cookies.*` | Save / delete login cookies |

Users only ever see and fetch their own downloads.

### 7. Usage

1. Run the migration: `php artisan migrate` (creates `ut_media_downloads`).
2. Run `php artisan utility:media-check`. Every row should say **OK**.
3. Open **Media Downloader** in the sidebar, paste a URL and click **Analyze**.
4. Click **Download** on a format. A progress card shows %, speed and ETA. If yt-dlp can't report a
   size, the bar is indeterminate rather than showing a made-up percentage. When the download
   finishes, the browser saves the file automatically; **Save file** is also shown.

### Images (TikTok photo posts, carousels)

yt-dlp only handles video and audio. For images the package uses **gallery-dl**, which is optional.
When yt-dlp finds no video in a link, or can't read it at all (for example TikTok `/photo/…` posts),
the link is checked with gallery-dl. If it has images, an **Images** section appears. Every image
has a blurred preview (press and hold to view) and its own download button, and **Download all (ZIP)**
gets every image in post order. Photo posts inside a bulk download (for example a TikTok profile)
get all their images as one ZIP.

```bash
pipx install gallery-dl            # or: python3 -m pip install -U gallery-dl
```

Set `GALLERY_DL_BINARY` in `.env` if the web user can't find it on its `PATH`. `utility:media-check`
shows its status. It's marked OPTIONAL, because without it everything else still works.
gallery-dl uses the same saved login cookies as yt-dlp, and the same safety rules apply: argument
arrays only, the URL after `--`, and user config files ignored. If some images of a post fail
(for example rate-limited half-way), the download is reported as failed instead of returning an
incomplete set.

### Bulk download (playlists, channels, profiles)

Paste a link that contains many videos: a YouTube playlist or channel (`/@name/videos`,
`/@name/shorts`), a TikTok profile (`/@user`), a Vimeo channel, or any page yt-dlp reads as a
list. Analyze shows every item with a checkbox. A channel home page shows its tabs
(Videos / Live / Shorts), which you open with **Open**.

1. Tick the videos you want (**Select all** is on by default).
2. Pick one quality for all of them. Presets mean "this height or the best one below it", so a
   720p-only video in a "1080p" bulk download still downloads at 720p. Audio-only (original) and
   MP3 are also available.
3. Click **Download selected**. Every item becomes its own download: it gets its own progress,
   appears in the history, and can be re-downloaded or cancelled on its own. The page follows the
   whole batch and picks it up again after a reload.
4. When the batch finishes, click **Download all as ZIP**. The ZIP is stored without
   re-compression (media is already compressed), so it builds quickly. You can also save items
   one by one.

Notes:
- Listing is fast because it uses `--flat-playlist`: each video is only fully analysed when its
  own turn comes. At most `MEDIA_DOWNLOADER_MAX_BATCH` items (default 200) are read from a list.
- Without a queue, the items run one after another in the PHP process that handled the request.
  For big batches, turn on the queue (`MEDIA_DOWNLOADER_QUEUE=true`, then `php artisan queue:work`).
  Running more workers downloads several items in parallel.
- Finished files of a batch are kept until the whole batch is done plus `MEDIA_DOWNLOADER_FILE_LIFETIME`,
  so the ZIP always contains everything.
- Supported list types depend on yt-dlp. Right now **Facebook pages/profiles are not supported** by
  yt-dlp, and its **Instagram profile** support is broken. Single Facebook/Instagram links work.
- The browser only sends the list URL and item numbers. The URLs to download come from the
  server's own analysis, and each one passes the SSRF check again.

### Private media (your own login cookies)

yt-dlp can download anything **your own account** can see (private videos, friends-only posts, private
groups, saved reels…) when it is given that account's cookies:

1. In a browser where you are logged in, install the extension **"Get cookies.txt LOCALLY"**.
2. Open the site (facebook.com, youtube.com, instagram.com…) and export its cookies (Netscape format).
   Several sites can go into one file.
3. On the Media Downloader page, open **Login cookies**, then upload the file.

How the cookies are handled:
- They are stored per user, **encrypted with `APP_KEY`**, in `storage/app/private/media-downloader-cookies`
  (`MEDIA_DOWNLOADER_COOKIES_DIR`). Only the cookie domains are ever shown. Cookie values are never sent
  to the browser or written to logs.
- For each yt-dlp run a plain-text copy is written with mode `0600` and deleted in a `finally` block
  as soon as the run ends.
- They are only used for the user who uploaded them, and analysis caches are kept separate per user.
- If you rotate `APP_KEY`, saved cookies become unreadable and are ignored. Upload them again.
- Sites log sessions out from time to time. When private links start failing with "requires signing
  in", export and upload the cookies again.
- Treat the server like a password manager: anyone with server access and `APP_KEY` could use those
  sessions.

### 8. Artisan commands

| Command | Description |
|---|---|
| `php artisan utility:media-check` | Checks Laravel, PHP, yt-dlp, FFmpeg, storage and queue mode |
| `php artisan utility:media-version [--check-latest]` | Shows the yt-dlp version, detects how it was installed (apt, pip, pipx, brew, snap, standalone) and prints the right update command. It never updates anything itself. |
| `php artisan utility:media-cleanup` | Deletes expired, failed and cancelled temp files, and marks downloads stuck by a crashed worker as failed |

`utility:media-cleanup` is registered on the scheduler **hourly** automatically (set
`schedule_cleanup` to `false` in the config to disable). Your server must run the Laravel scheduler:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Queue configuration

By default (`MEDIA_DOWNLOADER_QUEUE=false`) no worker is needed. The job runs in the same PHP process
right after the HTTP response is sent (`dispatch()->afterResponse()`), and the browser polls for progress.
This relies on PHP-FPM (`fastcgi_finish_request`). Under `php artisan serve` the start request stays
open until the download ends, but it still works.

For long downloads or several users, use a real queue:

```dotenv
MEDIA_DOWNLOADER_QUEUE=true
# Defaults: connection "media-downloader" (registered by the package: the app's database
# queue driver, with a retry_after longer than the longest download), queue "media"
```

Don't use a connection whose `retry_after` is shorter than a download can take. Laravel's database
default is 90 s, and with `tries = 1` it would mark every longer download as failed. The package's
`media-downloader` connection avoids this without changing the app's own queue config.

```bash
php artisan queue:table && php artisan migrate      # only for the database driver
php artisan queue:work media-downloader --queue=media --tries=1   # keep it running with supervisor
```

The job's own `$timeout` is `YT_DLP_TIMEOUT + YT_DLP_ANALYZE_TIMEOUT + 60`. Keep the worker's
`retry_after` (in `config/queue.php`) higher than that. The job has `tries = 1` because a
half-finished download is not safely retryable. The user can simply start it again.

### 10. Troubleshooting

| Symptom | Fix |
|---|---|
| `utility:media-check` → yt-dlp FAIL | Install yt-dlp, or set `YT_DLP_BINARY` to its absolute path. Check the web or worker user's `PATH`. |
| Most YouTube links fail, log says *Unable to extract …* | yt-dlp is outdated. Run `php artisan utility:media-version` and update. Distro packages are often too old. |
| Log says *No supported JavaScript runtime* / HTTP 403 on YouTube | Install deno (see section 3) and update yt-dlp. |
| "processed (FFmpeg failed)" or merge errors | Install FFmpeg, or set `FFMPEG_BINARY` to its absolute path. |
| Progress stays at *Waiting to start…* | Queue enabled but no worker is running. Start `queue:work`. |
| "not enough free disk space" | Free space, or lower `MEDIA_DOWNLOADER_MIN_FREE`. |
| "permission error" | Make `MEDIA_DOWNLOADER_TEMP` writable by the web and worker user. |
| "requires signing in" / "private" | Upload your login cookies for that site (or refresh expired ones). |
| 429 Too many requests | Rate limit reached. Adjust `MEDIA_DOWNLOADER_THROTTLE_*`. |

Technical details (yt-dlp stderr, exit codes) are written to the application log with the
`[MediaDownloader]` prefix. Users only ever see a friendly message.

### 11. Security considerations

- **SSRF**: only `http`/`https` URLs are accepted, on the default ports 80 and 443. URLs with embedded
  credentials are rejected, as are `localhost`, `*.local`, `*.internal` and other single-label LAN
  names. The host is resolved (A and AAAA), and the request is refused if **any** address is
  loopback, private, link-local (e.g. `169.254.169.254`), CGNAT, reserved, or IPv4-mapped IPv6 of
  those. This check runs when you analyze and again when the job runs. yt-dlp resolves hosts itself
  and follows redirects. For the strictest setup, set `MEDIA_DOWNLOADER_ALLOWED_HOSTS` and run
  workers on a host whose egress firewall blocks internal networks.
- **No shell injection**: processes run through Laravel's Process API with argument **arrays**. The URL
  is always placed after `--`, so it can't be read as an option. `--ignore-config` stops a
  user- or system-level yt-dlp config (which could add cookies, for example) from being loaded.
  Format selectors are built on the server. The client sends only a key, which is checked against
  a regex and against the formats of the analysed media.
- **Files**: output is always `media.<ext>` inside `<temp>/<uuid>/` (mode 0700). The download name is a
  slug of the title and is never taken from the URL. Files are only served from inside the temp
  root (checked with `realpath`), only to their owner, and are deleted after sending. Every failure
  or cancel path removes the directory in a `finally` block, and the scheduled cleanup removes
  anything left over.
- **Limits**: rate-limited analyze and download routes, a max file size, a free-space check, and
  timeouts on analysis and download.
- **Privacy of errors**: raw yt-dlp JSON, stderr and stack traces are never returned to the browser.

### Testing

```bash
composer update orchestra/testbench phpunit/phpunit --with-all-dependencies
composer test        # or vendor/bin/phpunit
```

The tests fake every yt-dlp process (`Process::fake()`) and DNS lookup. Nothing is downloaded from
the internet.
