<?php

namespace ThemeEngine\api;

use context\Context;

/** Request context passed to section providers and page-ready event listeners. */
final class ThemePageContext
{
    public function __construct(
        public readonly Context $awt,
        public readonly ThemeRecord $theme,
        public readonly string $pageKey,
        public readonly array $settings,
        public readonly array $data,
    ) {}
}
