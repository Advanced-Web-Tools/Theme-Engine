<?php

namespace ThemeEngine\api;

use ThemeEngine\api\contracts\ISectionProvider;

/** A package-relative JSON file or a PHP section provider with a stable source key. */
final class SectionSource
{
    private function __construct(
        public readonly ?string $file,
        public readonly ?ISectionProvider $provider,
        public readonly ?string $key,
        public readonly bool $static = false,
    ) {}

    public static function json(string $relativeFile): self
    {
        return new self($relativeFile, null, null);
    }

    /** A static provider uses its implementation, key, page key and saved theme settings as inputs. */
    public static function provider(ISectionProvider $provider, string $stableKey, bool $static = false): self
    {
        if (trim($stableKey) === '') {
            throw new \InvalidArgumentException('Provider source key is required.');
        }
        return new self(null, $provider, $stableKey, $static);
    }
}
