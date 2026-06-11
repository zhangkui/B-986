<?php
declare(strict_types=1);

/**
 * Generate local default banner JPEGs so /uploads/default/*.jpg always exists.
 * This prevents broken ad images when DB seed values point to default upload paths.
 */
final class DefaultBannerGenerator
{
    private const WIDTH = 1200;
    private const HEIGHT = 360;

    /** @var array<string, array{title: string, subtitle: string, from: string, to: string}> */
    private array $banners = [
        'home_banner.jpg' => [
            'title' => '不良反应填报平台',
            'subtitle' => '药品 / 医疗器械 / 化妆品',
            'from' => '#1d4ed8',
            'to' => '#10b981',
        ],
        'level2_banner.jpg' => [
            'title' => '分类知识',
            'subtitle' => '报告前请先了解',
            'from' => '#0369a1',
            'to' => '#0ea5e9',
        ],
        'level2_cat1_banner.jpg' => [
            'title' => '药品安全',
            'subtitle' => '不良反应报告',
            'from' => '#0f766e',
            'to' => '#14b8a6',
        ],
        'level2_cat2_banner.jpg' => [
            'title' => '医疗器械安全',
            'subtitle' => '不良事件报告',
            'from' => '#4f46e5',
            'to' => '#6366f1',
        ],
        'level2_cat3_banner.jpg' => [
            'title' => '化妆品安全',
            'subtitle' => '不良反应报告',
            'from' => '#be185d',
            'to' => '#ec4899',
        ],
        'level3_cat1_banner.jpg' => [
            'title' => '药品填报须知',
            'subtitle' => '请确认关键信息',
            'from' => '#0f766e',
            'to' => '#10b981',
        ],
        'level3_cat2_banner.jpg' => [
            'title' => '器械填报须知',
            'subtitle' => '请确认关键信息',
            'from' => '#4338ca',
            'to' => '#8b5cf6',
        ],
        'level3_cat3_banner.jpg' => [
            'title' => '化妆品填报须知',
            'subtitle' => '请确认关键信息',
            'from' => '#be123c',
            'to' => '#f43f5e',
        ],
    ];

    public function run(string $targetDir): void
    {
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException("Cannot create banner dir: {$targetDir}");
        }

        foreach ($this->banners as $filename => $meta) {
            $path = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path) && filesize($path) > 0) {
                continue;
            }
            $this->createBanner($path, $meta['title'], $meta['subtitle'], $meta['from'], $meta['to']);
        }
    }

    private function createBanner(string $path, string $title, string $subtitle, string $from, string $to): void
    {
        $im = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if (!$im) {
            throw new RuntimeException('Failed to create canvas.');
        }

        [$r1, $g1, $b1] = $this->hexToRgb($from);
        [$r2, $g2, $b2] = $this->hexToRgb($to);

        for ($x = 0; $x < self::WIDTH; $x++) {
            $ratio = $x / max(1, self::WIDTH - 1);
            $r = (int) round($r1 + ($r2 - $r1) * $ratio);
            $g = (int) round($g1 + ($g2 - $g1) * $ratio);
            $b = (int) round($b1 + ($b2 - $b1) * $ratio);
            $color = imagecolorallocate($im, $r, $g, $b);
            imageline($im, $x, 0, $x, self::HEIGHT, $color);
        }

        $overlay = imagecolorallocatealpha($im, 255, 255, 255, 95);
        imagefilledellipse($im, self::WIDTH - 120, 70, 220, 220, $overlay);
        imagefilledellipse($im, self::WIDTH - 250, self::HEIGHT - 20, 300, 300, $overlay);

        $white = imagecolorallocate($im, 255, 255, 255);
        $line = imagecolorallocatealpha($im, 255, 255, 255, 70);

        for ($i = 0; $i < 5; $i++) {
            imageline($im, 0, 40 + ($i * 28), self::WIDTH, 20 + ($i * 28), $line);
        }

        // Note: GD imagestring doesn't support Chinese characters well.
        // Using SVG fallback on frontend for proper Chinese text rendering.
        // This image serves as a gradient background only.

        imagejpeg($im, $path, 88);
        imagedestroy($im);
        @chmod($path, 0644);
    }

    /** @return array{int,int,int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [25, 137, 250];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}

try {
    $generator = new DefaultBannerGenerator();
    $generator->run('/var/www/html/uploads/default');
    echo "[banner-generator] default banners ready\n";
} catch (Throwable $e) {
    // Do not block service startup for placeholder generation failure.
    error_log('[banner-generator] ' . $e->getMessage());
}

