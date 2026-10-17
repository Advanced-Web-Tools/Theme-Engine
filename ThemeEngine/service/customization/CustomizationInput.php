<?php

namespace ThemeEngine\service\customization;

/** Validates HTTP fields without coupling customization services to PHP globals. */
final class CustomizationInput
{
    public const TABS = ['themes', 'layout', 'settings', 'widgets', 'presets', 'help'];

    public function __construct(
        private readonly array $query,
        private readonly array $post,
    ) {}

    public function tab(): string
    {
        return self::normalizeTab($this->query['tab'] ?? $this->post['tab'] ?? 'themes');
    }

    public function postedTab(): string
    {
        return self::normalizeTab($this->post['tab'] ?? 'themes');
    }

    public function operation(): mixed
    {
        return $this->post['operation'] ?? '';
    }

    public function hasThemeSelection(): bool
    {
        return isset($this->post['theme_id']);
    }

    public function string(string $key): string
    {
        $value = $this->post[$key] ?? null;
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Invalid field: ' . $key);
        }
        return $value;
    }

    public function array(string $key): array
    {
        $value = $this->post[$key] ?? [];
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Invalid field: ' . $key);
        }
        return $value;
    }

    public function positiveId(string $key): int
    {
        $value = $this->string($key);
        if (!ctype_digit($value) || (int) $value < 1) {
            throw new \InvalidArgumentException('Invalid ID.');
        }
        return (int) $value;
    }

    public static function themeId(array|string $params): int
    {
        $value = is_array($params) ? ($params['package_id'] ?? null) : null;
        if (!is_string($value) || !ctype_digit($value) || (int) $value < 1) {
            throw new \InvalidArgumentException('Invalid theme ID.');
        }
        return (int) $value;
    }

    private static function normalizeTab(mixed $tab): string
    {
        return is_string($tab) && in_array($tab, self::TABS, true) ? $tab : 'themes';
    }
}
