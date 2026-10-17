<?php

namespace ThemeEngine\service;

use event\EventDispatcher;
use ThemeEngine\api\events\ThemeLoadedEvent;
use ThemeEngine\api\SectionSource;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\service\contracts\IStore;
use URPC\api\contracts\IURPCDocument;

/** Theme selection, configuration and presets; public API used by runtimes and themes. */
final class Engine
{
    private ?ThemeRecord $active = null;
    private ?ThemeDefinition $definition = null;
    private bool $loadedDispatched = false;
    public ?string $loadError = null;
    public readonly SidebarWidgetRegistry $widgets;

    public function __construct(
        public readonly ThemeRegistry $registry,
        public readonly IStore $store,
        public readonly JsonSourceCache $cache,
        public readonly EventDispatcher $dispatcher,
        ?SidebarWidgetRegistry $widgets = null,
    ) {
        $this->widgets = $widgets ?? new SidebarWidgetRegistry($dispatcher);
    }

    public static function defaultState(): array
    {
        return [
            'active_theme_package_id' => null,
            'sections' => ['header' => true, 'footer' => true, 'side_l' => false, 'side_r' => false],
        ];
    }

    public function boot(): void
    {
        $id = $this->store->state()['active_theme_package_id'] ?? null;
        if ($id === null) {
            return;
        }
        try {
            $definition = $this->registry->load((int) $id);
            $this->active = $this->registry->get((int) $id);
            $this->definition = $definition;
        } catch (\Throwable $exception) {
            $this->loadError = $exception->getMessage();
        }
    }

    public function hasActiveTheme(): bool
    {
        return $this->active !== null;
    }

    public function activeTheme(): ThemeRecord
    {
        return $this->active ?? throw new \LogicException('No active theme.');
    }

    public function definition(): ThemeDefinition
    {
        return $this->definition ?? throw new \LogicException('No active theme definition.');
    }

    /** Activation is persisted now; public routes switch on the next request. */
    public function activate(int $packageId): void
    {
        $definition = $this->registry->load($packageId);
        $theme = $this->registry->get($packageId);
        foreach ($definition->sections as $source) {
            $this->warmSource($theme, $source);
        }
        foreach ($definition->pages as $page) {
            foreach ($page->sections as $source) {
                $this->warmSource($theme, $source);
            }
        }

        $this->store->transaction(function () use ($packageId) {
            $state = $this->store->state();
            $state['active_theme_package_id'] = $packageId;
            $this->store->saveState($state);
        });
    }

    /** Select a theme for editing/rendering without changing site activation or public routes. */
    public function forTheme(int $packageId): self
    {
        $selected = clone $this;
        $selected->definition = $this->registry->load($packageId);
        $selected->active = $this->registry->get($packageId);
        $selected->loadError = null;
        return $selected;
    }

    public function forPreview(int $packageId): self
    {
        return $this->forTheme($packageId);
    }

    public function announceLoaded(): void
    {
        if ($this->active !== null && !$this->loadedDispatched) {
            $this->loadedDispatched = true;
            $this->dispatcher->dispatch(new ThemeLoadedEvent($this->active, $this->definition()));
        }
    }

    public function settings(): array
    {
        $schema = $this->definition()->settings;
        $stored = $this->store->config($this->activeTheme()->packageId)['settings'];
        return (new SettingsSchema())->values($schema, $stored);
    }

    public function saveSettings(array $input): void
    {
        $values = (new SettingsSchema())->submitted($this->definition()->settings, $input);
        $this->updateConfig(fn($config) => array_replace($config, ['settings' => $values]));
    }

    public function resetSettings(): void
    {
        $this->updateConfig(fn($config) => array_replace($config, ['settings' => []]));
    }

    public function globalSections(): array
    {
        $legacy = $this->store->state()['sections'] ?? [];
        $sections = $this->hasActiveTheme()
            ? ($this->store->config($this->activeTheme()->packageId)['sections'] ?? $legacy)
            : $legacy;
        return array_replace(self::defaultState()['sections'], $sections);
    }

    public function pageOverrides(): array
    {
        return $this->store->config($this->activeTheme()->packageId)['pages'] ?? [];
    }

    public function sectionsFor(string $pageKey): array
    {
        $this->assertPage($pageKey);
        $sections = $this->globalSections();
        foreach (($this->pageOverrides()[$pageKey] ?? []) as $name => $value) {
            if (in_array($name, ThemeDefinition::OPTIONAL, true) && is_bool($value)) {
                $sections[$name] = $value;
            }
        }
        $sections['content'] = true;
        return $sections;
    }

    public function saveGlobalSections(array $input): void
    {
        $values = $this->validateSections($input);
        if ($this->hasActiveTheme()) {
            $this->updateConfig(fn($config) => array_replace($config, ['sections' => $values]));
            return;
        }

        $this->store->transaction(function () use ($values) {
            $state = $this->store->state();
            $state['sections'] = $values;
            $this->store->saveState($state);
        });
    }

    public function savePageSections(string $pageKey, array $input): void
    {
        $this->assertPage($pageKey);
        if (array_diff_key($input, array_flip(ThemeDefinition::OPTIONAL))) {
            throw new \InvalidArgumentException('Unknown page section.');
        }
        $values = [];
        foreach (ThemeDefinition::OPTIONAL as $name) {
            $value = $input[$name] ?? 'inherit';
            if (!in_array($value, ['inherit', 'on', 'off'], true)) {
                throw new \InvalidArgumentException('Invalid page section override.');
            }
            if ($value !== 'inherit') {
                $values[$name] = $value === 'on';
            }
        }
        $this->updateConfig(function ($config) use ($pageKey, $values) {
            $config['pages'][$pageKey] = $values;
            return $config;
        });
    }

    /** Theme-wide sidebar placements, persisted in the selected theme's config. */
    public function globalWidgets(): array
    {
        return array_replace(['side_l' => [], 'side_r' => []],
            $this->store->config($this->activeTheme()->packageId)['widgets'] ?? []);
    }

    public function pageWidgetOverrides(): array
    {
        return $this->store->config($this->activeTheme()->packageId)['page_widgets'] ?? [];
    }

    public function widgetsFor(string $pageKey): array
    {
        $this->assertPage($pageKey);
        return array_replace($this->globalWidgets(), $this->pageWidgetOverrides()[$pageKey] ?? []);
    }

    public function saveGlobalWidgets(array $input): void
    {
        $values = (new SidebarWidgetSettings())->validate($input, $this->widgets->all(), $this->globalWidgets());
        $this->updateConfig(fn($config) => array_replace($config, ['widgets' => $values]));
    }

    /** Missing/null sidebar inherits the theme; an empty array explicitly removes its widgets. */
    public function savePageWidgets(string $pageKey, array $input): void
    {
        $this->assertPage($pageKey);
        $values = (new SidebarWidgetSettings())->validate($input, $this->widgets->all(),
            $this->pageWidgetOverrides()[$pageKey] ?? [], true);
        $this->updateConfig(function ($config) use ($pageKey, $values) {
            if ($values === []) unset($config['page_widgets'][$pageKey]);
            else $config['page_widgets'][$pageKey] = $values;
            return $config;
        });
    }

    public function createDocument(ThemePageContext $context): IURPCDocument
    {
        if ($context->theme->packageId !== $this->activeTheme()->packageId) {
            throw new \LogicException('Page belongs to an inactive theme.');
        }
        $this->assertPage($context->pageKey);
        return (new ThemeDocumentBuilder($this->cache, $this->widgets))->build(
            $this->definition(),
            $context,
            $this->sectionsFor($context->pageKey),
            $this->widgetsFor($context->pageKey),
        );
    }

    public function presets(): array
    {
        return $this->store->presets($this->activeTheme()->packageId);
    }

    public function savePreset(string $name): int
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 120) {
            throw new \InvalidArgumentException('Preset name must contain 1–120 bytes.');
        }
        return $this->store->transaction(fn() => $this->store->savePreset(
            $this->activeTheme()->packageId,
            $name,
            [
                'version' => 1,
                'settings' => $this->settings(),
                'pages' => $this->pageOverrides(),
                'sections' => $this->globalSections(),
                'widgets' => $this->globalWidgets(),
                'page_widgets' => $this->pageWidgetOverrides(),
            ],
        ));
    }

    public function applyPreset(int $id): void
    {
        $selected = null;
        foreach ($this->presets() as $preset) {
            if ((int) $preset['id'] === $id) {
                $selected = $preset['payload'];
            }
        }
        if ($selected === null || ($selected['version'] ?? null) !== 1) {
            throw new \OutOfBoundsException('Unknown or incompatible preset.');
        }

        // Revalidate the snapshot against the current theme schema and known pages.
        $settings = (new SettingsSchema())->values($this->definition()->settings, $selected['settings']);
        $sections = $this->validateSections($selected['sections']);
        $pages = [];
        foreach ($selected['pages'] as $key => $overrides) {
            if (!isset($this->definition()->pages[$key])) {
                continue;
            }
            foreach ($overrides as $slot => $value) {
                if (in_array($slot, ThemeDefinition::OPTIONAL, true) && is_bool($value)) {
                    $pages[$key][$slot] = $value;
                }
            }
        }
        // Legacy presets predate widgets: retain existing placements when absent.
        $widgets = $this->globalWidgets();
        $pageWidgets = $this->pageWidgetOverrides();
        if (isset($selected['widgets'])) {
            $widgets = (new SidebarWidgetSettings())->validate($selected['widgets'], $this->widgets->all(), $selected['widgets']);
            $pageWidgets = [];
            foreach (($selected['page_widgets'] ?? []) as $key => $placements) {
                if (isset($this->definition()->pages[$key])) {
                    $pageWidgets[$key] = (new SidebarWidgetSettings())->validate($placements, $this->widgets->all(), $placements, true);
                }
            }
        }
        $this->store->transaction(function () use ($settings, $sections, $pages, $widgets, $pageWidgets) {
            $this->store->saveConfig(
                $this->activeTheme()->packageId,
                array_replace($this->store->config($this->activeTheme()->packageId), [
                    'settings' => $settings,
                    'pages' => $pages,
                    'sections' => $sections,
                    'widgets' => $widgets,
                    'page_widgets' => $pageWidgets,
                ]),
            );
        });
    }

    public function deletePreset(int $id): void
    {
        $this->store->deletePreset($this->activeTheme()->packageId, $id);
    }

    private function updateConfig(callable $callback): void
    {
        $packageId = $this->activeTheme()->packageId;
        $this->store->transaction(fn() => $this->store->saveConfig(
            $packageId,
            $callback($this->store->config($packageId)),
        ));
    }

    private function validateSections(array $values): array
    {
        if (array_diff_key($values, array_flip(ThemeDefinition::OPTIONAL))) {
            throw new \InvalidArgumentException('Unknown layout section.');
        }
        $result = [];
        foreach (ThemeDefinition::OPTIONAL as $name) {
            $value = $values[$name] ?? false;
            if (!in_array($value, [true, false, '0', '1', 0, 1], true)) {
                throw new \InvalidArgumentException('Invalid section visibility.');
            }
            $result[$name] = in_array($value, [true, 1, '1'], true);
        }
        return $result;
    }

    private function assertPage(string $key): void
    {
        if (!isset($this->definition()->pages[$key])) {
            throw new \OutOfBoundsException('Unknown theme page.');
        }
    }

    private function warmSource(ThemeRecord $theme, ?SectionSource $source): void
    {
        if ($source?->file !== null) {
            $this->cache->load(
                ThemeRegistry::resolveFile($theme->root, $source->file),
                $theme->name . '/' . $source->file,
            );
        }
    }
}
