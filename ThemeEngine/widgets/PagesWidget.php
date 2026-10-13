<?php

namespace ThemeEngine\widgets;

use Dashboard\api\widget\DashboardWidget;
use ThemeEngine\service\Engine;
use URPC\api\URPCBlock;
use URPC\api\URPCBlockDescriptor;

final class PagesWidget extends DashboardWidget
{
    public function __construct(private readonly Engine $engine)
    {
        parent::__construct();
    }

    public function createChildren(): array
    {
        $title = $this->textBlock('h2', 'Available pages', 'card-title');
        $header = new URPCBlock(new URPCBlockDescriptor('header'));
        $header->setBlockChildren([$title]);

        if (!$this->engine->hasActiveTheme()) {
            return [$header, $this->textBlock('p', 'Activate a theme to see its pages.', 'text-small text-muted mt-3 mb-0')];
        }

        $definition = $this->engine->definition();
        if ($definition->pages === []) {
            return [$header, $this->textBlock('p', 'No pages available.', 'text-small text-muted mt-3 mb-0')];
        }

        $paths = [];
        foreach ($definition->routes as $route) {
            $paths[$route->pageKey][] = $route->path;
        }

        $items = [];
        foreach (array_slice($definition->pages, 0, 4) as $page) {
            $details = new URPCBlock(new URPCBlockDescriptor('div'));
            $children = [$this->textBlock('strong', $page->label, 'text-small')];

            foreach ($paths[$page->key] ?? [] as $path) {
                $isLink = !str_contains($path, '{') && !str_contains($path, '}')
                    && !str_starts_with($path, '//') && !str_contains($path, '\\');
                $route = $this->textBlock($isLink ? 'a' : 'span', $path, 'd-block text-small text-muted');
                if ($isLink) {
                    $route->setBlockAttributes(array_replace($route->getBlockAttributes(), ['href' => $path]));
                }
                $children[] = $route;
            }

            $details->setBlockChildren($children);
            $item = new URPCBlock(new URPCBlockDescriptor('li'));
            $item->setBlockChildren([$details]);
            $items[] = $item;
        }

        $list = new URPCBlock(new URPCBlockDescriptor('ul'));
        $list->setBlockAttributes(['class' => 'list-group mt-3']);
        $list->setBlockChildren($items);

        return [$header, $list];
    }

    public function widget(): DashboardWidget
    {
        $descriptor = new URPCBlockDescriptor('section');
        $descriptor->setSourceKey('ThemeEngine/widgets/pages');
        $this->setBlockDescriptor($descriptor);
        $this->setSize(self::SIZE_MEDIUM);
        $this->setBlockAttributes(array_replace($this->getBlockAttributes(), ['class' => 'card min-w-0']));
        $this->setBlockChildren($this->createChildren());

        return $this;
    }

    private function textBlock(string $tag, string $content, string $class): URPCBlock
    {
        $block = new URPCBlock(new URPCBlockDescriptor($tag));
        $block->setBlockContent($content);
        $block->setBlockAttributes(['class' => $class]);

        return $block;
    }
}
