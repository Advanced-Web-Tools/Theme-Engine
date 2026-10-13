<?php

namespace ThemeEngine\service\customization;

use response\Response;

/** Serves package assets while keeping font requests inside a fixed allowlist. */
final class CustomizationAssets
{
    private const FONT_FILES = [
        'fa-solid-900.woff2',
        'fa-regular-400.woff2',
        'fa-brands-400.woff2',
        'fa-v4compatibility.woff2',
    ];

    public function __construct(private readonly string $root) {}

    public function defaultPreview(): Response
    {
        return $this->asset('default_preview.png', 'image/png');
    }

    public function layoutCss(): Response
    {
        return $this->asset('layout.css', 'text/css');
    }

    public function themesCss(): Response
    {
        return $this->asset('themes.css', 'text/css');
    }

    public function themesJs(): Response
    {
        return $this->asset('themes.js', 'application/javascript');
    }

    public function previewThemeJs(): Response
    {
        return Response::make()->file(PACKAGES . 'Dashboard/views/assets/js/theme.js')->asJs();
    }

    public function fontAwesomeCss(): Response
    {
        return Response::make()->file(SRC . 'vendor/fontawesome-free-6.5-web/css/all.min.css')->asCss();
    }

    public function fontAwesomeFont(array|string $params): Response
    {
        $font = is_array($params) ? ($params['font'] ?? null) : null;
        if (!in_array($font, self::FONT_FILES, true)) {
            return Response::make(404)->asText();
        }
        // AWT has no font sender: the binary sender preserves the explicit inline MIME headers.
        return Response::make()
            ->file(SRC . 'vendor/fontawesome-free-6.5-web/webfonts/' . $font)
            ->asFile()
            ->header('Content-Type', 'font/woff2')
            ->header('Content-Disposition', 'inline');
    }

    private function asset(string $file, string $mime): Response
    {
        return Response::make()->file($this->root . '/assets/' . $file)->as($mime);
    }
}
