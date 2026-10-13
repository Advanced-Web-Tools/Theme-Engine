<?php

namespace ThemeEngine\api;

use ThemeEngine\service\SettingsSchema;

/** Validated declaration returned by an installed theme's ITheme implementation. */
final class ThemeDefinition
{
    public const SLOTS = ['header', 'side_l', 'content', 'side_r', 'footer'];
    public const OPTIONAL = ['header', 'footer', 'side_l', 'side_r'];

    /** @param array<string, PageDefinition> $pages @param list<RouteDefinition> $routes */
    public function __construct(
        public readonly string $label,
        public readonly array $pages,
        public readonly array $routes,
        public readonly array $sections = [],
        public readonly array $settings = [],
        public readonly array $stylesheets = [],
        public readonly array $scripts = [],
    ) {
        if (trim($label) === '') {
            throw new \InvalidArgumentException('Theme label is required.');
        }
        foreach ($pages as $key => $page) {
            if (!$page instanceof PageDefinition || $key !== $page->key) {
                throw new \InvalidArgumentException('Pages must be keyed by their stable key.');
            }
        }
        foreach ($sections as $slot => $source) {
            if (!in_array($slot, self::SLOTS, true) || !$source instanceof SectionSource) {
                throw new \InvalidArgumentException('Invalid theme section.');
            }
        }
        $this->validateRoutes();
        foreach (array_merge($stylesheets, $scripts) as $url) {
            self::validateAsset($url);
        }
        (new SettingsSchema())->validateDefinition($settings);
    }

    private function validateRoutes(): void
    {
        $paths = [];
        foreach ($this->routes as $route) {
            if (!$route instanceof RouteDefinition || !isset($this->pages[$route->pageKey])) {
                throw new \InvalidArgumentException('Route references an unknown page.');
            }
            $path = rtrim($route->path, '/') ?: '/';
            if ($path === '/dashboard' || str_starts_with($path, '/dashboard/')
                || $path === '/storage' || str_starts_with($path, '/storage/')) {
                throw new \InvalidArgumentException('Theme route uses a reserved path.');
            }

            // The same shape with different capture names is still a duplicate route.
            $shape = preg_replace('/\{[^}]+\}/', '{}', $path);
            if (isset($paths[$shape])) {
                throw new \InvalidArgumentException('Duplicate theme route: ' . $path);
            }
            $paths[$shape] = true;
        }
    }

    private static function validateAsset(mixed $url): void
    {
        if (!is_string($url) || preg_match('/[\x00-\x20]/', $url) || str_starts_with($url, '//')
            || (!str_starts_with($url, '/') && !preg_match('~^https?://~i', $url))) {
            throw new \InvalidArgumentException('Asset URL must be a site path or HTTP(S) URL.');
        }
    }
}
