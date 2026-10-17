<?php

namespace ThemeEngine\api\events;

use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use ThemeEngine\api\SidebarWidgetDefinition;

/** Convenience listener for a package contributing one sidebar widget. */
final class SidebarWidgetRegistrationListener implements IEventListener
{
    public function __construct(private readonly SidebarWidgetDefinition $definition) {}

    public function handle(IEvent $event): array
    {
        if (!$event instanceof SidebarWidgetRegistryEvent) {
            throw new \InvalidArgumentException('Expected SidebarWidgetRegistryEvent.');
        }
        $event->getRegistry()->register($this->definition);
        return [];
    }
}
