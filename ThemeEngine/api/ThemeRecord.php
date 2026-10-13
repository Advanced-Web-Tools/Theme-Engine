<?php

namespace ThemeEngine\api;

/** Installed package metadata; availability is independent of site activation. */
final class ThemeRecord
{
    public function __construct(
        public readonly int $packageId,
        public readonly string $name,
        public readonly string $root,
        public readonly string $version,
        public readonly bool $enabled,
        public readonly ?string $description = null,
        public readonly ?string $icon = null,
        public readonly ?string $previewImage = null,
        public readonly array $dependencies = [],
        public readonly ?string $author = null,
        public readonly ?string $license = null,
        public readonly ?string $licenseUrl = null,
        public readonly ?string $minimumAwtVersion = null,
        public readonly ?string $maximumAwtVersion = null,
        public readonly ?string $installedAt = null,
    ) {}
}
