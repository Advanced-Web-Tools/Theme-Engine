<?php

namespace ThemeEngine\service;

use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use ThemeEngine\api\events\SidebarWidgetRegistryEvent;
use ThemeEngine\api\SidebarWidgetDefinition;
use ThemeEngine\widgets\sidebar\CurrentPageWidget;
use ThemeEngine\widgets\sidebar\DateWidget;
use ThemeEngine\widgets\sidebar\PagesWidget;

/** Built-in widgets use the same registration event as other packages. */
final class DefaultSidebarWidgets implements IEventListener
{
    public function __construct(private readonly ThemeRegistry $themes) {}

    public function handle(IEvent $event): array
    {
        if (!$event instanceof SidebarWidgetRegistryEvent) return [];
        foreach ([
            new SidebarWidgetDefinition('ThemeEngine/pages', 'Pages', new PagesWidget($this->themes)),
            new SidebarWidgetDefinition('ThemeEngine/current-page', 'Current page', new CurrentPageWidget($this->themes)),
            new SidebarWidgetDefinition('ThemeEngine/date', 'Today', new DateWidget()),
        ] as $widget) {
            $event->getRegistry()->register($widget);
        }
        return [];
    }
}
