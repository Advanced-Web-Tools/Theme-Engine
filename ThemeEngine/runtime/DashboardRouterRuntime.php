<?php

namespace ThemeEngine\runtime;

use router\Router;
use runtime\api\RuntimeRouterAPI;

final class DashboardRouterRuntime extends RuntimeRouterAPI
{
    public function main(): void
    {
        $runtime = $this->getPassable(ControllerRuntime::class)
            ?? throw new \LogicException('Controller runtime must execute first.');
        $controller = $runtime->getController('customization');

        // Keep route ordering and existing names stable; only saves require POST and CSRF.
        $routes = [
            ['/dashboard/customization', 'index', 'theme_engine.customization'],
            ['/dashboard/customization/themes/{package_id}', 'theme', 'theme_engine.theme'],
            ['/dashboard/customization/themes/{package_id}/preview', 'preview', 'theme_engine.theme.preview'],
            ['/dashboard/customization/default-preview.png', 'defaultPreview', 'theme_engine.default_preview'],
            ['/dashboard/customization/preview-theme.js', 'previewThemeJs', 'theme_engine.preview.theme_js'],
            ['/dashboard/customization/fontawesome/css/all.min.css', 'fontAwesomeCss', 'theme_engine.fontawesome.css'],
            ['/dashboard/customization/fontawesome/webfonts/{font}', 'fontAwesomeFont', 'theme_engine.fontawesome.font'],
            ['/dashboard/customization/themes.js', 'themesJs', 'theme_engine.themes.js'],
            ['/dashboard/customization/themes.css', 'themesCss', 'theme_engine.themes.css'],
            ['/dashboard/customization/save', 'save', 'theme_engine.customization.save', 'POST'],
            ['/dashboard/customization/layout.css', 'layoutCss', 'theme_engine.layout.css'],
        ];
        foreach ($routes as $definition) {
            [$path, $action, $name] = $definition;
            $route = new Router($path, $action, clone $controller);
            if (isset($definition[3])) {
                $route->setMethod($definition[3]);
            }
            $this->addRouter($route->setName($name));
        }
    }
}
