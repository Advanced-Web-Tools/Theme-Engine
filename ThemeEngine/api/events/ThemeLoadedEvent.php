<?php

namespace ThemeEngine\api\events;

use event\interfaces\IEvent;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemeRecord;

final class ThemeLoadedEvent implements IEvent
{
    public const NAME = 'theme_engine.theme.loaded';

    public function __construct(
        public readonly ThemeRecord $theme,
        public readonly ThemeDefinition $definition,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function bundle(): array
    {
        return ['theme' => $this->theme, 'definition' => $this->definition];
    }
}
