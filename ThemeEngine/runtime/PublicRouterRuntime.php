<?php

namespace ThemeEngine\runtime;

use router\Router;
use runtime\api\RuntimeRouterAPI;
use ThemeEngine\service\Engine;

final class PublicRouterRuntime extends RuntimeRouterAPI
{
    public function main(): void
    {
        $engine = $this->getShared('ThemeEngine', 'engine', Engine::class)
            ?? throw new \LogicException('Theme loader must execute first.');
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
