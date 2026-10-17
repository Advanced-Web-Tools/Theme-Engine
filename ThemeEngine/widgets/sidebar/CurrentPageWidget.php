<?php

namespace ThemeEngine\widgets\sidebar;

use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\service\LayoutBuilder;
use ThemeEngine\service\ThemeRegistry;

final class CurrentPageWidget implements ISectionProvider
{
    public function __construct(private readonly ThemeRegistry $themes) {}

    public function createBlocks(ThemePageContext $context): array
    {
        $page = $this->themes->load($context->theme->packageId)->pages[$context->pageKey]
            ?? throw new \OutOfBoundsException('Unknown widget page.');
        return [
            LayoutBuilder::block('h2', 'Current page', ['class' => 'te-widget-title']),
            LayoutBuilder::block('p', $page->label),
        ];
    }
}
