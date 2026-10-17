<?php

namespace ThemeEngine\service\preview;

use render\TemplateEngine\BladeOne;
use router\security\Csrf;
use ThemeEngine\service\Engine;
use ThemeEngine\service\customization\CustomizationActions;
use ThemeEngine\service\customization\CustomizationInput;

/** Uses the dashboard forms and validation for the theme being previewed. */
final class PreviewSettings
{
    public function __construct(private readonly Engine $engine, private readonly string $views) {}

    public function save(int $packageId, array $post): void
    {
        $input = new CustomizationInput([], $post);
        if (!in_array($input->operation(), ['settings', 'reset_settings', 'layout', 'page', 'preset_save', 'preset_apply', 'preset_delete', 'cache_clear'], true)) {
            throw new \InvalidArgumentException('Unknown preview settings operation.');
        }
        // The authenticated route determines the theme; never trust a posted theme ID.
        (new CustomizationActions())->apply($this->engine->forTheme($packageId), $input);
    }

    public function render(int $packageId): string
    {
        $theme = $this->engine->registry->get($packageId);
        $engine = $this->engine->forTheme($packageId);
        $blade = new BladeOne($this->views, COMPILED, BladeOne::MODE_AUTO);
        $blade->setFileExtension('.awt.php');
        $blade->setPackageName('ThemeEngine');
        return $blade->run('preview/settings.awt.php', [
            'selectedTheme' => $theme,
            'previewPageRoutes' => array_map(static fn($route) => $route->pageKey, $engine->definition()->routes),
            'definition' => $engine->definition(),
            'values' => $engine->settings(),
            'global' => $engine->globalSections(),
            'overrides' => $engine->pageOverrides(),
            'presets' => $engine->presets(),
            'token' => (new Csrf())->token(),
        ]);
    }
}
