<?php

namespace ThemeEngine\runtime;

use context\Context;
use object\ObjectFactory;
use runtime\api\RuntimeControllerAPI;
use ThemeEngine\controllers\CustomizationController;
use ThemeEngine\controllers\ThemePage;
use ThemeEngine\service\Engine;

final class ControllerRuntime extends RuntimeControllerAPI
{
    public function main(): void
    {
        $engine = $this->getShared('ThemeEngine', 'engine', Engine::class)
            ?? throw new \LogicException('Theme loader must execute first.');
        $factory = (new ObjectFactory())
            ->setClassName(CustomizationController::class)
            ->setType(CustomizationController::class)
            ->setProperties(['eventDispatcher' => $this->eventDispatcher, 'localAssetPath' => ''])
            ->setConstructorArgs([$engine])
            ->addMethodCall('setRootPath')
            ->addMethodArgs('setRootPath', [$this->rootPath])
            ->addMethodCall('setViewPath')
            ->addMethodArgs('setViewPath', ['/views/']);
        $this->addController($factory, 'customization');

        // Public controllers receive their theme package context, not the engine's package context.
        $pages = [];
        if ($engine->hasActiveTheme()) {
            foreach ($engine->definition()->routes as $index => $route) {
                $theme = $engine->activeTheme();
                $context = new Context($theme->root . '/', $theme->name, (string) $theme->packageId);
                $pages[$index] = (clone $route->controller)
                    ->setType(ThemePage::class)
                    ->addMethodCall('configureTheme')
                    ->addMethodArgs('configureTheme', [$engine, $route->pageKey])
                    ->addMethodCall('setContext')
                    ->addMethodArgs('setContext', [$context])
                    ->addMethodCall('setRootPath')
                    ->addMethodArgs('setRootPath', [$theme->root])
                    ->addProperty('packageName', $theme->name);
            }
        }
        $this->setShared('pageFactories', $pages);
    }
}
