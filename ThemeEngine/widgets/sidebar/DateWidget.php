<?php

namespace ThemeEngine\widgets\sidebar;

use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\service\LayoutBuilder;

final class DateWidget implements ISectionProvider
{
    public function createBlocks(ThemePageContext $context): array
    {
        $today = new \DateTimeImmutable('today');
        return [
            LayoutBuilder::block('h2', 'Today', ['class' => 'te-widget-title']),
            LayoutBuilder::block('time', $today->format('j F Y'), ['datetime' => $today->format('Y-m-d')]),
        ];
    }
}
