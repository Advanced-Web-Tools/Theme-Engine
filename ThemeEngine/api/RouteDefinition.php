<?php

namespace ThemeEngine\api;

use object\ObjectFactory;

final class RouteDefinition
{
    public function __construct(
        public readonly string $path,
        public readonly string $pageKey,
        public readonly ObjectFactory $controller,
        public readonly string $action = 'index',
        public readonly string $method = 'GET',
    ) {
        if ($path === '' || $path[0] !== '/' || str_contains($path, '?') || str_contains($path, '#')) {
            throw new \InvalidArgumentException('Invalid route path.');
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $action)) {
            throw new \InvalidArgumentException('Invalid route action.');
        }
        if ($method !== 'GET') {
            throw new \InvalidArgumentException('Theme page routes support GET (including AWT HEAD) in this MVP.');
        }
    }
}
