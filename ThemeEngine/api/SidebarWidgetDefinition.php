<?php

namespace ThemeEngine\api;

use ThemeEngine\api\contracts\ISectionProvider;

/** A sidebar widget produces fresh URPC blocks through the existing provider contract. */
final class SidebarWidgetDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ISectionProvider $provider,
    ) {
        if (!preg_match('~^[a-zA-Z0-9][a-zA-Z0-9_./-]{0,127}$~D', $key) || trim($label) === '') {
            throw new \InvalidArgumentException('A widget needs a stable key and a label.');
        }
    }
}
