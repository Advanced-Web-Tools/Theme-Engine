<?php

namespace ThemeEngine\service;

/** Standalone preview shell; the selected theme is rendered separately in its frame. */
final class ThemePreviewWindow
{
    public const DEVICES = [
        'desktop' => ['100%', '100%'],
        'tablet' => [768, 1024],
        'mobile' => [390, 844],
    ];

    public function __construct(private readonly ?SiteAppearance $appearance = null) {}

    public function render(string $themeName, string $previewUrl, string $device = 'desktop', ?string $settingsUrl = null): string
    {
        if (!isset(self::DEVICES[$device])) {
            throw new \InvalidArgumentException('Unknown preview device size.');
        }
        [$width, $height] = self::DEVICES[$device];
        $widthCss = is_int($width) ? $width . 'px' : $width;
        $heightCss = is_int($height) ? $height . 'px' : $height;
        $appearance = $this->appearance?->settings();
        $title = SiteAppearance::title($appearance['title_format'] ?? 'theme', 'Preview') ?? ($themeName . ' · Preview');
        $title = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $favicon = SiteAppearance::faviconUrl($appearance['favicon'] ?? null);
        $favicon = $favicon === null ? null : htmlspecialchars($favicon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $url = htmlspecialchars($previewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $settingsUrl = $settingsUrl === null ? null : htmlspecialchars($settingsUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $devices = self::DEVICES;
        $icons = ['desktop' => 'desktop', 'tablet' => 'tablet-screen-button', 'mobile' => 'mobile-screen-button'];

        // A plain PHP template keeps this renderer usable independently of Blade or AWT globals.
        ob_start();
        try {
            require __DIR__ . '/../views/preview/window.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
