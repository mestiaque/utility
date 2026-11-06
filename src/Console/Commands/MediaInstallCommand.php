<?php

namespace ME\Utility\Console\Commands;

use Illuminate\Console\Command;
use ME\Utility\Services\MediaDownloader\ToolInstaller;
use RuntimeException;

/**
 * Downloads yt-dlp and FFmpeg into the app's storage (no root needed) — the terminal
 * version of the page's "Server setup" → "Install / update tools".
 */
class MediaInstallCommand extends Command
{
    protected $signature = 'utility:media-install
                            {tool?* : yt-dlp and/or ffmpeg (default: both)}';

    protected $description = 'Install or update yt-dlp and FFmpeg inside storage (for hosts without root, e.g. cPanel)';

    public function handle(ToolInstaller $installer): int
    {
        $tools = $this->argument('tool') ?: [ToolInstaller::YT_DLP, ToolInstaller::FFMPEG];
        $ok    = true;

        foreach ($tools as $tool) {
            if (! in_array($tool, [ToolInstaller::YT_DLP, ToolInstaller::FFMPEG], true)) {
                $this->error("Unknown tool: {$tool}");
                $ok = false;
                continue;
            }

            $this->line("Installing {$tool}…");
            try {
                $this->info("  {$tool}: " . $installer->install($tool) . ' → ' . $installer->path($tool));
            } catch (RuntimeException $e) {
                $this->error("  {$tool}: " . $e->getMessage());
                $ok = false;
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
