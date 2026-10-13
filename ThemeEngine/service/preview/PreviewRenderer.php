<?php

namespace ThemeEngine\service\preview;

use ThemeEngine\service\Engine;
use ThemeEngine\service\ThemePreview;
use ThemeEngine\service\ThemePreviewWindow;
use ThemeEngine\service\customization\CustomizationUrls;

/** Builds either a theme page or the standalone window that embeds it. */
final class PreviewRenderer
{
    public function __construct(private readonly Engine $engine) {}

    public function render(int $packageId, array $query): string
    {
        $route = $query['route'] ?? '0';
        if (!is_string($route) || !ctype_digit($route)) {
            throw new \InvalidArgumentException('Invalid preview route.');
        }
        $parameters = $query['params'] ?? [];
        if (!is_array($parameters)) {
            throw new \InvalidArgumentException('Invalid route parameters.');
        }
        if (!isset($query['window'])) {
            return (new ThemePreview($this->engine))->render($packageId, (int) $route, $parameters);
        }
        if ($query['window'] !== '1') {
            throw new \InvalidArgumentException('Invalid preview window.');
        }

        $theme = $this->engine->registry->get($packageId);
        $definition = $this->engine->registry->load($packageId);
        if (!isset($definition->routes[(int) $route])) {
            throw new \OutOfBoundsException('Unknown theme route.');
        }
        $device = $query['device'] ?? 'desktop';
        if (!is_string($device) || !isset(ThemePreviewWindow::DEVICES[$device])) {
            throw new \InvalidArgumentException('Unknown preview device size.');
        }

        // The inner frame renders a page, not another standalone window.
        unset($query['window'], $query['device']);
        $url = CustomizationUrls::preview($packageId, $query);
        return (new ThemePreviewWindow())->render($theme->name, $url, $device,
            '/dashboard/customization/themes/' . $packageId . '/preview-settings');
    }

    public static function errorHtml(\Throwable $exception): string
    {
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Theme preview</title></head>'
            . '<body><h1>Preview unavailable</h1><p>' . $message . '</p></body></html>';
    }
}
