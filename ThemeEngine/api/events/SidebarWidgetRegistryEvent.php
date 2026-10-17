<?php

namespace ThemeEngine\api\events;

use event\interfaces\IEvent;
use ThemeEngine\service\SidebarWidgetRegistry;

/** Fired on first use, after package runtimes have installed their listeners. */
final class SidebarWidgetRegistryEvent implements IEvent
{
    public const NAME = 'theme_engine.widgets.register';

    public function __construct(private readonly SidebarWidgetRegistry $registry) {}

    public function getRegistry(): SidebarWidgetRegistry
    {
        return $this->registry;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function bundle(): array
    {
        return ['registry' => $this->registry];
    }
}
