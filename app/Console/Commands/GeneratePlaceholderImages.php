<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates locally-served placeholder images for each event category.
 *
 * The events dataset has no real images, so we synthesise a small set of
 * rich, layered gradient SVGs per category (3 variants each). Each image
 * combines a multi-stop gradient, soft glow blobs, a category-specific
 * geometric motif, a subtle grain texture and editorial typography. They're
 * written to public/images/events/{type}/{n}.svg and assigned to events
 * deterministically by EventPresenter — satisfying "two or more images per
 * event, served locally" without bloating the 1M+ row table.
 */
class GeneratePlaceholderImages extends Command
{
    protected $signature = 'images:generate-placeholders';

    protected $description = 'Generate local placeholder images for each event category.';

    /**
     * Per-category visual identity: three gradient stops, an accent for the
     * motif/typography, and the geometric motif used as an overlay.
     *
     * @var array<string, array{stops: array{0: string, 1: string, 2: string}, motif: string}>
     */
    private const CATEGORIES = [
        'concert' => ['stops' => ['#4c1d95', '#7c3aed', '#db2777'], 'motif' => 'waves'],
        'conference' => ['stops' => ['#1e3a8a', '#2563eb', '#4f46e5'], 'motif' => 'grid'],
        'meetup' => ['stops' => ['#065f46', '#0d9488', '#22c55e'], 'motif' => 'rings'],
        'workshop' => ['stops' => ['#7c2d12', '#ea580c', '#f59e0b'], 'motif' => 'mesh'],
        'festival' => ['stops' => ['#701a75', '#c026d3', '#fb7185'], 'motif' => 'confetti'],
        'sports' => ['stops' => ['#14532d', '#059669', '#84cc16'], 'motif' => 'rays'],
        'networking' => ['stops' => ['#164e63', '#0891b2', '#38bdf8'], 'motif' => 'nodes'],
        'exhibition' => ['stops' => ['#4c1d95', '#6d28d9', '#64748b'], 'motif' => 'frames'],
    ];

    /**
     * Composition presets per variant: gradient direction and the focal
     * positions of the two glow blobs. Keeps the three images of a set
     * visually distinct while sharing a colour family.
     *
     * @var list<array{angle: array{0: int, 1: int, 2: int, 3: int}, glowA: array{0: int, 1: int}, glowB: array{0: int, 1: int}}>
     */
    private const VARIANTS = [
        ['angle' => [0, 0, 1, 1], 'glowA' => [640, 130], 'glowB' => [120, 520]],
        ['angle' => [0, 1, 1, 0], 'glowA' => [160, 150], 'glowB' => [660, 470]],
        ['angle' => [0, 0, 1, 0], 'glowA' => [400, 90], 'glowB' => [400, 560]],
    ];

    public function handle(): int
    {
        foreach (self::CATEGORIES as $type => $identity) {
            $dir = public_path("images/events/{$type}");

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            foreach (self::VARIANTS as $index => $variant) {
                $svg = $this->svg($type, $identity, $variant, $index + 1);
                file_put_contents("{$dir}/".($index + 1).'.svg', $svg);
            }
        }

        $count = count(self::CATEGORIES) * count(self::VARIANTS);
        $this->info("Generated {$count} placeholder images in public/images/events.");

        return self::SUCCESS;
    }

    /**
     * @param  array{stops: array{0: string, 1: string, 2: string}, motif: string}  $identity
     * @param  array{angle: array{0: int, 1: int, 2: int, 3: int}, glowA: array{0: int, 1: int}, glowB: array{0: int, 1: int}}  $variant
     */
    private function svg(string $type, array $identity, array $variant, int $n): string
    {
        $label = Str::title($type);
        $id = "{$type}-{$n}";
        [$s0, $s1, $s2] = $identity['stops'];
        [$ax, $ay, $bx, $by] = $variant['angle'];
        [$gax, $gay] = $variant['glowA'];
        [$gbx, $gby] = $variant['glowB'];

        $motif = $this->motif($identity['motif'], $n);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600" role="img" aria-label="{$label} event">
          <defs>
            <linearGradient id="bg-{$id}" x1="{$ax}" y1="{$ay}" x2="{$bx}" y2="{$by}">
              <stop offset="0" stop-color="{$s0}"/>
              <stop offset="0.55" stop-color="{$s1}"/>
              <stop offset="1" stop-color="{$s2}"/>
            </linearGradient>
            <radialGradient id="glow-{$id}" cx="0.5" cy="0.5" r="0.5">
              <stop offset="0" stop-color="#ffffff" stop-opacity="0.55"/>
              <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
            </radialGradient>
            <linearGradient id="sheen-{$id}" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0" stop-color="#ffffff" stop-opacity="0.18"/>
              <stop offset="0.5" stop-color="#ffffff" stop-opacity="0"/>
              <stop offset="1" stop-color="#000000" stop-opacity="0.28"/>
            </linearGradient>
            <filter id="soft-{$id}" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="60"/>
            </filter>
            <filter id="grain-{$id}">
              <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" stitchTiles="stitch" result="n"/>
              <feColorMatrix in="n" type="saturate" values="0"/>
            </filter>
            <clipPath id="clip-{$id}"><rect width="800" height="600"/></clipPath>
          </defs>

          <g clip-path="url(#clip-{$id})">
            <rect width="800" height="600" fill="url(#bg-{$id})"/>
            <circle cx="{$gax}" cy="{$gay}" r="260" fill="url(#glow-{$id})" filter="url(#soft-{$id})"/>
            <circle cx="{$gbx}" cy="{$gby}" r="220" fill="url(#glow-{$id})" filter="url(#soft-{$id})" opacity="0.7"/>
            <g opacity="0.9">{$motif}</g>
            <rect width="800" height="600" fill="url(#sheen-{$id})"/>
            <rect width="800" height="600" filter="url(#grain-{$id})" opacity="0.05"/>
            <g font-family="'Instrument Sans','Segoe UI',system-ui,sans-serif">
              <rect x="48" y="476" width="34" height="3" rx="1.5" fill="#ffffff" opacity="0.85"/>
              <text x="92" y="481" font-size="20" font-weight="600" fill="#ffffff" opacity="0.75" letter-spacing="5">EVENT</text>
              <text x="46" y="540" font-size="62" font-weight="700" fill="#ffffff" letter-spacing="-1">{$label}</text>
            </g>
          </g>
        </svg>
        SVG;
    }

    /**
     * Returns the SVG fragment for a category's geometric overlay motif.
     * Stroke/fill is white at low opacity; the variant number nudges the
     * placement so the three images differ.
     */
    private function motif(string $motif, int $n): string
    {
        $shift = ($n - 1) * 36;
        $w = 'stroke="#ffffff" fill="none"';

        return match ($motif) {
            'waves' => $this->each(5, fn (int $i) => sprintf(
                '<path d="M-20 %1$d Q 180 %2$d 400 %1$d T 820 %1$d" %3$s stroke-width="2" opacity="%4$.2f"/>',
                120 + $i * 90 + $shift % 90,
                70 + $i * 90 + $shift % 90,
                $w,
                0.10 + $i * 0.02,
            )),

            'grid' => $this->grid(60, $shift),

            'rings' => $this->each(7, fn (int $i) => sprintf(
                '<circle cx="%1$d" cy="%2$d" r="%3$d" %4$s stroke-width="2" opacity="0.10"/>',
                620 - $shift, 150 + $shift, 60 + $i * 78, $w,
            )),

            'mesh' => $this->each(14, fn (int $i) => sprintf(
                '<line x1="%1$d" y1="0" x2="%2$d" y2="600" %3$s stroke-width="1.5" opacity="0.10"/>',
                -200 + $i * 90 + $shift, 100 + $i * 90 + $shift, $w,
            )),

            'confetti' => $this->each(26, fn (int $i) => sprintf(
                '<rect x="%1$d" y="%2$d" width="14" height="14" rx="3" fill="#ffffff" opacity="0.12" transform="rotate(%3$d %4$d %5$d)"/>',
                ($i * 137 + $shift) % 760 + 20,
                ($i * 211 + $shift * 2) % 480 + 20,
                ($i * 47) % 90,
                ($i * 137 + $shift) % 760 + 27,
                ($i * 211 + $shift * 2) % 480 + 27,
            )),

            'rays' => $this->each(12, fn (int $i) => sprintf(
                '<line x1="400" y1="300" x2="%1$d" y2="%2$d" %3$s stroke-width="2" opacity="0.08"/>',
                (int) round(400 + 700 * cos(($i * 30 + $shift) * M_PI / 180)),
                (int) round(300 + 700 * sin(($i * 30 + $shift) * M_PI / 180)),
                $w,
            )),

            'nodes' => $this->nodes($shift),

            'frames' => $this->each(5, fn (int $i) => sprintf(
                '<rect x="%1$d" y="%1$d" width="%2$d" height="%3$d" rx="10" %4$s stroke-width="2" opacity="0.10"/>',
                60 + $i * 36 + $shift % 36,
                680 - $i * 72,
                480 - $i * 72,
                $w,
            )),

            default => '',
        };
    }

    /**
     * @param  callable(int): string  $fn
     */
    private function each(int $count, callable $fn): string
    {
        $out = '';

        for ($i = 0; $i < $count; $i++) {
            $out .= $fn($i);
        }

        return $out;
    }

    private function grid(int $step, int $shift): string
    {
        $out = '';

        for ($x = ($shift % $step); $x <= 800; $x += $step) {
            for ($y = ($shift % $step); $y <= 600; $y += $step) {
                $out .= sprintf('<circle cx="%d" cy="%d" r="2.2" fill="#ffffff" opacity="0.12"/>', $x, $y);
            }
        }

        return $out;
    }

    private function nodes(int $shift): string
    {
        $points = [[120, 140], [300, 90], [470, 200], [650, 120], [720, 320], [560, 430], [360, 360], [180, 470], [430, 540]];
        $out = '';

        foreach ($points as $i => [$x, $y]) {
            $nx = ($x + $shift) % 760 + 20;
            $next = $points[($i + 1) % count($points)];
            $mx = ($next[0] + $shift) % 760 + 20;

            $out .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#ffffff" stroke-width="1.5" opacity="0.10"/>',
                $nx, $y, $mx, $next[1],
            );
            $out .= sprintf('<circle cx="%d" cy="%d" r="6" fill="#ffffff" opacity="0.18"/>', $nx, $y);
        }

        return $out;
    }
}
