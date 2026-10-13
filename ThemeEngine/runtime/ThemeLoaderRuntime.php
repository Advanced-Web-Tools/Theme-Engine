<?php

namespace ThemeEngine\runtime;

use Dashboard\api\events\WidgetRegistrationListener;
use Dashboard\api\events\WidgetRegistryEvent;
use Dashboard\api\widget\DashboardWidget;
use Dashboard\api\widget\WidgetDefinition;
use database\provider\DatabaseProvider;
use object\ObjectFactory;
use runtime\api\RuntimeAPI;
use ThemeEngine\service\DatabaseStore;
use ThemeEngine\service\Engine;
use ThemeEngine\service\JsonSourceCache;
use ThemeEngine\service\PageReadyBridge;
use ThemeEngine\service\ThemeRegistry;
use ThemeEngine\service\ThemePackageLoader;
use ThemeEngine\widgets\PagesWidget;
use ThemeEngine\widgets\ThemeEngineWidget;
use URPC\api\events\BeforeRenderEvent;

final class ThemeLoaderRuntime extends RuntimeAPI
{
    public function main(): void
    {
        $provider = new DatabaseProvider();
        $registry = $this->loadThemeRegistry($provider);
        $cacheRoot = rtrim(COMPILED, '/\\') . '/ThemeEngine';

        $engine = new Engine(
            $registry,
            new DatabaseStore($provider),
            new JsonSourceCache($cacheRoot),
            $this->eventDispatcher,
        );
        $engine->boot();

        $this->eventDispatcher->addListener(
            BeforeRenderEvent::NAME,
            new PageReadyBridge($this->eventDispatcher),
        );
        $this->setShared('engine', $engine);

        $this->registerWidgets($engine);
    }

    private function loadThemeRegistry(DatabaseProvider $provider): ThemeRegistry
    {
        return (new ThemePackageLoader($provider, PACKAGES))->load();
    }

    private function registerWidgets(Engine $engine): void
    {
        $appearanceWidget = new WidgetDefinition(
            key: 'ThemeEngine/appearance',
            packageName: 'ThemeEngine',
            label: 'Your appearence',
            description: 'Your active theme and a shortcut to customization.',
            factory: (new ObjectFactory())
                ->setClassName(ThemeEngineWidget::class)
                ->setType(DashboardWidget::class)
                ->setConstructorArgs([$engine]),
            defaultPosition: 1,
            size: DashboardWidget::SIZE_MEDIUM,
        );

        $pagesWidget = new WidgetDefinition(
            key: 'ThemeEngine/pages',
            packageName: 'ThemeEngine',
            label: 'Available pages',
            description: 'Pages provided by the active theme and their routes.',
            factory: (new ObjectFactory())
                ->setClassName(PagesWidget::class)
                ->setType(DashboardWidget::class)
                ->setConstructorArgs([$engine]),
            defaultPosition: 2,
            size: DashboardWidget::SIZE_MEDIUM,
        );

        foreach ([$appearanceWidget, $pagesWidget] as $widget) {
            $this->eventDispatcher->addListener(
                WidgetRegistryEvent::NAME,
                new WidgetRegistrationListener($widget),
            );
        }
    }
}
