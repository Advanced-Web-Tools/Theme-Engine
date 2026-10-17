<?php

namespace ThemeEngine\service;

use event\EventDispatcher;
use ThemeEngine\api\events\SidebarWidgetRegistryEvent;
use ThemeEngine\api\SidebarWidgetDefinition;
use ThemeEngine\api\ThemePageContext;
use URPC\api\contracts\IURPCBlock;

/** Shared across selected theme clones; registration does not persist PHP objects. */
final class SidebarWidgetRegistry
{
    private array $definitions = [];
    private bool $discovered = false;

    public function __construct(private readonly ?EventDispatcher $dispatcher = null) {}

    public function register(SidebarWidgetDefinition $widget): void
    {
        if (isset($this->definitions[$widget->key])) {
            throw new \InvalidArgumentException('Duplicate sidebar widget: ' . $widget->key);
        }
        $this->definitions[$widget->key] = $widget;
    }

    /** @return array<string, SidebarWidgetDefinition> */
    public function all(): array
    {
        $this->discover();
        return $this->definitions;
    }

    /** @return list<IURPCBlock> */
    public function render(array $keys, ThemePageContext $context, string $sidebar): array
    {
        $this->discover();
        $result = [];
        foreach ($keys as $key) {
            $widget = $this->definitions[$key] ?? null;
            // Disabled/uninstalled packages may leave saved placements behind.
            if ($widget === null) continue;
            $children = $widget->provider->createBlocks($context);
            foreach ($children as $child) {
                if (!$child instanceof IURPCBlock) {
                    throw new \UnexpectedValueException('Sidebar widgets must return URPC blocks.');
                }
            }
            if ($children === []) continue;
            $result[] = LayoutBuilder::block('div', '', [
                'class' => 'te-widget', 'data-te-widget' => $key,
            ], $children, $context->theme->name . '/widgets/' . $sidebar . '/' . $key);
        }
        return $result;
    }

    private function discover(): void
    {
        if ($this->discovered || $this->dispatcher === null) return;
        $before = $this->definitions;
        $this->discovered = true; // Listeners may inspect the registry while registering.
        try {
            $this->dispatcher->dispatch(new SidebarWidgetRegistryEvent($this));
        } catch (\Throwable $exception) {
            $this->definitions = $before;
            $this->discovered = false;
            throw $exception;
        }
    }
}
