<?php

namespace ThemeEngine\api;

final class PageDefinition
{
    /** Page sections override theme defaults; an explicit null clears a default section. */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $sections = [],
    ) {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $key)) {
            throw new \InvalidArgumentException('Invalid page key.');
        }
        foreach ($sections as $slot => $source) {
            if (!in_array($slot, ThemeDefinition::SLOTS, true) || ($source !== null && !$source instanceof SectionSource)) {
                throw new \InvalidArgumentException('Invalid page section.');
            }
        }
    }
}
