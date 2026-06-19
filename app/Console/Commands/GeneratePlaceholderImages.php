<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates locally-served placeholder images for each event category.
 *
 * The events dataset has no real images, so we synthesise a small set of
 * tasteful gradient SVGs per category (3 variants each). They're written to
 * public/images/events/{type}/{n}.svg and assigned to events deterministically
 * by EventPresenter — satisfying "two or more images per event, served
 * locally" without bloating the 1M+ row table.
 */
class GeneratePlaceholderImages extends Command
{
    protected $signature = 'images:generate-placeholders';

    protected $description = 'Generate local placeholder images for each event category.';

    /**
     * Three gradient colour pairs per category.
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    private const PALETTES = [
        'concert' => [['#7c3aed', '#db2777'], ['#6d28d9', '#ec4899'], ['#8b5cf6', '#f43f5e']],
        'conference' => [['#2563eb', '#4f46e5'], ['#1d4ed8', '#4338ca'], ['#3b82f6', '#6366f1']],
        'meetup' => [['#0d9488', '#16a34a'], ['#0f766e', '#22c55e'], ['#14b8a6', '#4ade80']],
        'workshop' => [['#ea580c', '#f59e0b'], ['#c2410c', '#d97706'], ['#f97316', '#fbbf24']],
        'festival' => [['#c026d3', '#f43f5e'], ['#a21caf', '#e11d48'], ['#d946ef', '#fb7185']],
        'sports' => [['#059669', '#65a30d'], ['#047857', '#4d7c0f'], ['#10b981', '#84cc16']],
        'networking' => [['#0891b2', '#0284c7'], ['#0e7490', '#0369a1'], ['#06b6d4', '#38bdf8']],
        'exhibition' => [['#7c3aed', '#475569'], ['#6d28d9', '#334155'], ['#8b5cf6', '#64748b']],
    ];

    public function handle(): int
    {
        foreach (self::PALETTES as $type => $variants) {
            $dir = public_path("images/events/{$type}");

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            foreach ($variants as $index => [$from, $to]) {
                $svg = $this->svg($type, $from, $to, $index + 1);
                file_put_contents("{$dir}/".($index + 1).'.svg', $svg);
            }
        }

        $count = count(self::PALETTES) * 3;
        $this->info("Generated {$count} placeholder images in public/images/events.");

        return self::SUCCESS;
    }

    private function svg(string $type, string $from, string $to, int $variant): string
    {
        $label = Str::title($type);
        $gradientId = "g-{$type}-{$variant}";

        // Decorative circle positions shift per variant for subtle variety.
        $cx = [620, 180, 400][$variant - 1];
        $cy = [140, 460, 300][$variant - 1];

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600" role="img" aria-label="{$label}">
          <defs>
            <linearGradient id="{$gradientId}" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$from}"/>
              <stop offset="1" stop-color="{$to}"/>
            </linearGradient>
          </defs>
          <rect width="800" height="600" fill="url(#{$gradientId})"/>
          <circle cx="{$cx}" cy="{$cy}" r="220" fill="#ffffff" opacity="0.10"/>
          <circle cx="{$cx}" cy="{$cy}" r="140" fill="#ffffff" opacity="0.08"/>
          <text x="48" y="540" font-family="'Segoe UI', system-ui, sans-serif" font-size="56" font-weight="700" fill="#ffffff" opacity="0.95">{$label}</text>
          <text x="50" y="500" font-family="'Segoe UI', system-ui, sans-serif" font-size="22" font-weight="500" fill="#ffffff" opacity="0.7" letter-spacing="3">EVENT</text>
        </svg>
        SVG;
    }
}
