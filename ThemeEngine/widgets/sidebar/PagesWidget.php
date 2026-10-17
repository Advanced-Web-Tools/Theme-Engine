<?php

namespace ThemeEngine\widgets\sidebar;

use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\service\LayoutBuilder;
use ThemeEngine\service\ThemeRegistry;

/** Lists concrete public pages; routes needing parameters cannot be linked generically. */
final class PagesWidget implements ISectionProvider
{
    public function __construct(private readonly ThemeRegistry $themes) {}

    public function createBlocks(ThemePageContext $context): array
    {
        $definition = $this->themes->load($context->theme->packageId);
        $links = [];
        $seen = [];
        foreach ($definition->routes as $route) {
            if (str_contains($route->path, '{') || isset($seen[$route->pageKey])) continue;
            $seen[$route->pageKey] = true;
            $attributes = ['href' => $route->path];
            if ($route->pageKey === $context->pageKey) $attributes['aria-current'] = 'page';
            $links[] = LayoutBuilder::block('li', '', [], [
                LayoutBuilder::block('a', $definition->pages[$route->pageKey]->label, $attributes),
            ]);
        }
        if ($links === []) return [];
        return [
            LayoutBuilder::block('h2', 'Pages', ['class' => 'te-widget-title']),
            LayoutBuilder::block('nav', '', ['aria-label' => 'Pages'], [LayoutBuilder::block('ul', '', [], $links)]),
        ];
    }
}
