<?php

namespace ThemeEngine\service\customization;

use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\service\Engine;
use ThemeEngine\service\SiteAppearance;
use ThemeEngine\service\ThemePreview;

/** Keeps site activation separate from the theme being inspected or edited. */
final class CustomizationViewData
{
    public function __construct(private readonly Engine $engine) {}

    public function build(string $tab, ?ThemeRecord $selectedTheme = null): array
    {
        $editingEngine = $selectedTheme === null ? $this->engine : null;
        $themeRoutes = [];
        $themeError = null;
        $previewUrl = null;

        if ($selectedTheme !== null) {
            try {
                $editingEngine = $this->engine->forTheme($selectedTheme->packageId);
                $themeRoutes = (new ThemePreview($this->engine))->routes($selectedTheme->packageId);
                foreach ($themeRoutes as $route) {
                    if ($route['parameters'] === []) {
                        $previewUrl = CustomizationUrls::preview($selectedTheme->packageId, ['route' => $route['index']]);
                        break;
                    }
                }
            } catch (\Throwable $exception) {
                $themeError = $exception->getMessage();
            }
        }

        $canEdit = $editingEngine?->hasActiveTheme();
        $appearance = $tab === 'site' ? $this->engine->appearance->settings() : [];
        return [
            'engine' => $this->engine,
            'tab' => $tab,
            'customizationUrl' => CustomizationUrls::dashboard($selectedTheme),
            'selectedTheme' => $selectedTheme,
            'themes' => $this->themesWithActiveFirst(),
            'themeRoutes' => $themeRoutes,
            'themeError' => $themeError,
            'previewUrl' => $previewUrl,
            'definition' => $canEdit ? $editingEngine->definition() : null,
            'values' => $canEdit ? $editingEngine->settings() : [],
            'global' => $editingEngine?->globalSections() ?? Engine::defaultState()['sections'],
            'optional' => ThemeDefinition::OPTIONAL,
            'overrides' => $canEdit ? $editingEngine->pageOverrides() : [],
            'availableWidgets' => $canEdit ? $editingEngine->widgets->all() : [],
            'globalWidgets' => $canEdit ? $editingEngine->globalWidgets() : [],
            'pageWidgets' => $canEdit ? $editingEngine->pageWidgetOverrides() : [],
            'presets' => $canEdit ? $editingEngine->presets() : [],
            'titleFormats' => SiteAppearance::TITLE_FORMATS,
            'titleFormat' => $appearance['title_format'] ?? 'theme',
            'faviconUrl' => SiteAppearance::faviconUrl($appearance['favicon'] ?? null),
            'titleExamples' => $tab === 'site' ? $this->titleExamples() : [],
        ];
    }

    private function titleExamples(): array
    {
        $default = $this->engine->hasActiveTheme() ? 'Page · ' . $this->engine->definition()->label : 'Page';
        $examples = [];
        foreach (array_keys(SiteAppearance::TITLE_FORMATS) as $format) {
            $examples[$format] = SiteAppearance::title($format, 'Page') ?? $default;
        }
        return $examples;
    }

    private function themesWithActiveFirst(): array
    {
        $themes = $this->engine->registry->all();
        if (!$this->engine->hasActiveTheme()) {
            return $themes;
        }
        $activeId = $this->engine->activeTheme()->packageId;
        return [$activeId => $themes[$activeId]] + $themes;
    }
}
