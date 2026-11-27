<?php

namespace ThemeEngine\runtime;

use router\Router;
use runtime\api\RuntimeRouterAPI;
use ThemeEngine\service\Engine;
use ThemeEngine\service\SiteAppearance;

final class PublicRouterRuntime extends RuntimeRouterAPI
{
    public function main(): void
    {
        $engine = $this->getShared('ThemeEngine', 'engine', Engine::class)
            ?? throw new \LogicException('Theme loader must execute first.');
        $runtime = $this->getPassable(ControllerRuntime::class)
            ?? throw new \LogicException('Controller runtime must execute first.');
        $favicon = new Router(SiteAppearance::FAVICON_PATH, 'index', clone $runtime->getController('favicon'), service: true);
        $this->addRouter($favicon->setName('theme_engine.favicon'));
        if (!$engine->hasActiveTheme()) {
            return;
        }
        $factories = $this->getShared('ThemeEngine', 'pageFactories') ?? [];
        foreach ($engine->definition()->routes as $index => $route) {
            $this->addRouter((new Router($route->path, $route->action, clone $factories[$index]))
                ->setName('theme_engine.page.' . $index));
        }
        $engine->announceLoaded();
    }
}
