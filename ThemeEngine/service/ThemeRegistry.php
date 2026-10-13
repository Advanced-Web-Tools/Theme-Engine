<?php

namespace ThemeEngine\service;

use ThemeEngine\api\contracts\ITheme;
use ThemeEngine\api\SectionSource;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\controllers\ThemePage;

/** Installed theme records and validated definitions, cached for this request. */
final class ThemeRegistry
{
    private array $themes = [];
    private array $definitions = [];

    public function register(ThemeRecord $theme): void
    {
        if ($theme->packageId < 1 || isset($this->themes[$theme->packageId])) {
            throw new \InvalidArgumentException('Invalid or duplicate theme package ID.');
        }
        $this->themes[$theme->packageId] = $theme;
    }

    public function all(): array
    {
        return $this->themes;
    }

    public function get(int $id): ThemeRecord
    {
        return $this->themes[$id] ?? throw new \OutOfBoundsException('Unknown theme package.');
    }

    public function load(int $id): ThemeDefinition
    {
        if (isset($this->definitions[$id])) {
            return $this->definitions[$id];
        }
        $theme = $this->get($id);
        if (!$theme->enabled) {
            throw new \LogicException('Enable the theme package before selecting it.');
        }

        $file = self::resolveFile($theme->root, 'theme.php');
        $instance = (static function (string $path) { return require $path; })($file);
        if (!$instance instanceof ITheme) {
            throw new \UnexpectedValueException('theme.php must return ITheme.');
        }
        $definition = $instance->getDefinition();
        $this->validateControllers($definition);
        foreach ($definition->sections as $source) {
            $this->validateSource($theme, $source);
        }
        foreach ($definition->pages as $page) {
            foreach ($page->sections as $source) {
                $this->validateSource($theme, $source);
            }
        }
        return $this->definitions[$id] = $definition;
    }

    public static function resolveFile(string $root, string $relative): string
    {
        $base = realpath($root);
        $file = realpath(rtrim($root, '/\\') . DIRECTORY_SEPARATOR . $relative);
        if ($base === false || $file === false || !is_file($file) || !is_readable($file)
            || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException('Theme file must exist inside its package: ' . $relative);
        }
        return $file;
    }

    private function validateControllers(ThemeDefinition $definition): void
    {
        foreach ($definition->routes as $route) {
            $page = (clone $route->controller)->create();
            if (!$page instanceof ThemePage || !is_callable([$page, $route->action])) {
                throw new \UnexpectedValueException('Theme controllers must extend ThemePage and expose the route action.');
            }
            $method = new \ReflectionMethod($page, $route->action);
            $owner = $method->getDeclaringClass()->getName();
            if ($method->isStatic() || ($route->action !== 'index' && !is_subclass_of($owner, ThemePage::class))) {
                throw new \UnexpectedValueException('A page route must call index or an action declared by a theme page controller.');
            }
        }
    }

    private function validateSource(ThemeRecord $theme, ?SectionSource $source): void
    {
        if ($source?->file !== null) {
            self::resolveFile($theme->root, $source->file);
        }
    }
}
