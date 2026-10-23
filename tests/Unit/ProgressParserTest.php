<?php

namespace ME\Utility\Tests\Unit;

use ME\Utility\Services\MediaDownloader\ProgressParser;
use PHPUnit\Framework\TestCase;

class ProgressParserTest extends TestCase
{
    private array $events = [];

    private function parser(): ProgressParser
    {
        return new ProgressParser(function (?float $p, ?int $s, ?int $e, ?string $stage) {
            $this->events[] = compact('p', 's', 'e', 'stage');
        }, minInterval: 0);
    }

    public function test_parses_known_total(): void
    {
        $parser = $this->parser();
        $parser->feed("[download] Destination: /tmp/x/media.f137.mp4\n");
        $parser->feed("ut-progress|4200|10000|NA|2516582.4|12\n");

        $last = end($this->events);
        $this->assertSame(42.0, $last['p']);
        $this->assertSame(2516582, $last['s']);
        $this->assertSame(12, $last['e']);
        $this->assertSame('Downloading', $last['stage']);
    }

    public function test_uses_estimate_when_total_unknown(): void
    {
        $parser = $this->parser();
        $parser->feed("ut-progress|500|NA|1000.0|NA|NA\n");

        $this->assertSame(50.0, end($this->events)['p']);
    }

    public function test_unknown_size_reports_null_percent(): void
    {
        $parser = $this->parser();
        $parser->feed("ut-progress|500|NA|NA|100|NA\n");

        $this->assertNull(end($this->events)['p']);
    }

    public function test_handles_lines_split_across_chunks_and_stages(): void
    {
        $parser = $this->parser();
        $parser->feed("[download] Destination: a.f137.mp4\nut-progress|10|1");
        $parser->feed("00|NA|NA|NA\n[download] Destination: a.f140.m4a\n[Merger] Merging formats into \"media.mp4\"\n");
        $parser->flush();

        $stages = array_column($this->events, 'stage');
        $this->assertContains('Downloading (part 2)', $stages);
        $this->assertSame('Merging audio & video', end($this->events)['stage']);
        $this->assertNull(end($this->events)['p']);
        $this->assertSame(10.0, $this->events[1]['p']);
    }
}
