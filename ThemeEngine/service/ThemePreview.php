<?php

namespace ThemeEngine\service;

use context\Context;
use ThemeEngine\api\RouteDefinition;
use ThemeEngine\controllers\ThemePage;

/** Renders an installed theme without persisting activation or registering public routes. */
final class ThemePreview
{
    public function __construct(private readonly Engine $engine) {}

    public function routes(int $packageId): array
    {
        $definition = $this->engine->registry->load($packageId);
        $routes = [];
        foreach ($definition->routes as $index => $route) {
            $routes[] = [
                'index' => $index,
                'path' => $route->path,
                'method' => $route->method,
                'label' => $definition->pages[$route->pageKey]->label,
                'parameters' => $this->parameterNames($route),
            ];
        }
        return $routes;
    }

    public function render(int $packageId, int $routeIndex, array $parameters = []): string
    {
        $engine = $this->engine->forPreview($packageId);
        $route = $engine->definition()->routes[$routeIndex] ?? throw new \OutOfBoundsException('Unknown theme route.');
        $params = $this->routeParameters($route, $parameters);
        $page = $this->createPage($engine, $route);
        $result = $page->{$route->action}($params);
        if (!$result instanceof ThemePage) {
            throw new \UnexpectedValueException('This route does not return a theme page to preview.');
        }

        $pagePath = preg_replace_callback(
            '/\{([^}]+)\}/',
            static fn($match) => rawurlencode($params[$match[1]]),
            $route->path,
        );
        $siteUrl = $this->siteUrl();
        $html = $result->render();
        return (new ThemePreviewNavigation())->rewrite(
            $html,
            $packageId,
            $engine->definition(),
            $pagePath,
            $siteUrl,
        );
    }

    private function createPage(Engine $engine, RouteDefinition $route): ThemePage
    {
        $theme = $engine->activeTheme();
        $page = (clone $route->controller)->setType(ThemePage::class)->create();
        $page->configureTheme($engine, $route->pageKey);
        $page->setContext(new Context($theme->root . '/', $theme->name, (string) $theme->packageId));
        $page->setRootPath($theme->root);
        $page->packageName = $theme->name;
        $page->eventDispatcher = $engine->dispatcher;
        $page->viewName = $route->action;
        return $page;
    }

    private function parameterNames(RouteDefinition $route): array
    {
        preg_match_all('/\{([^}]+)\}/', $route->path, $matches);
        return $matches[1];
    }

    private function routeParameters(RouteDefinition $route, array $parameters): array
    {
        $params = [];
        foreach ($this->parameterNames($route) as $name) {
            $value = $parameters[$name] ?? null;
            if (!is_string($value) || trim($value) === '' || strlen($value) > 512) {
                throw new \InvalidArgumentException('Enter a value for route parameter: ' . $name);
            }
            $params[$name] = $value;
        }
        return $params;
    }

    private function siteUrl(): string
    {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        return isset($_SERVER['HTTP_HOST'])
            ? $scheme . '://' . $_SERVER['HTTP_HOST']
            : (defined('HOSTNAME') ? HOSTNAME : 'http://localhost');
    }
}
