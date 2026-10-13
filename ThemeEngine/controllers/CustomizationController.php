<?php

namespace ThemeEngine\controllers;

use Dashboard\api\page\DashboardControllerBase;
use DOMException;
use redirect\Redirect;
use response\Response;
use router\security\Csrf;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\service\Engine;
use ThemeEngine\service\customization\CustomizationActions;
use ThemeEngine\service\customization\CustomizationAssets;
use ThemeEngine\service\customization\CustomizationInput;
use ThemeEngine\service\customization\CustomizationUrls;
use ThemeEngine\service\customization\CustomizationViewData;
use ThemeEngine\service\preview\PreviewRenderer;
use view\View;

/** HTTP boundary for the customization dashboard; theme behavior lives in services. */
final class CustomizationController extends DashboardControllerBase
{
    // AWT populates these public properties from the view bundle during rendering.
    public Engine $engine;
    public string $tab;
    public string $customizationUrl;
    public ?string $error;
    public string $token;
    public bool $saved;
    public array $themes;
    public array $values;
    public array $global;
    public array $optional;
    public array $overrides;
    public array $presets;
    public ?ThemeDefinition $definition;
    public ?ThemeRecord $selectedTheme;
    public array $themeRoutes;
    public ?string $themeError;
    public ?string $previewUrl;

    public function __construct(private readonly Engine $themeEngine)
    {
        $this->setPermissionLevel(1);
        $this->checkAuth();
    }

    /** @throws DOMException */
    public function view(array $data = []): View
    {
        $data['navigation'] = $this->buildNavigation();
        $data['name'] = 'Customization';
        $this->bundle = $data;

        // Blade compiles during render; do not parse uncompiled directives as HTML.
        $this->createDocument();
        return $this;
    }

    public function render(): string
    {
        $previous = libxml_use_internal_errors(true);
        try {
            return parent::render();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function index(array|string $params = []): View
    {
        $this->viewName = 'customization';
        return $this->dashboard();
    }

    public function theme(array|string $params = []): View
    {
        $this->viewName = 'customization';
        try {
            $theme = $this->themeEngine->registry->get(CustomizationInput::themeId($params));
            return $this->dashboard(selectedTheme: $theme);
        } catch (\InvalidArgumentException|\OutOfBoundsException $exception) {
            http_response_code(404);
            return $this->dashboard('Theme not found.');
        }
    }

    public function save(array|string $params = []): View|Redirect
    {
        // Router enforces POST and CSRF; the constructor enforces dashboard authentication.
        $this->viewName = 'customization';
        $input = new CustomizationInput($_GET, $_POST);
        $selectedTheme = null;

        try {
            $engine = $this->themeEngine;
            if ($input->hasThemeSelection()) {
                $selectedTheme = $engine->registry->get($input->positiveId('theme_id'));
                if (!in_array($input->operation(), ['activate', 'cache_clear'], true)) {
                    $engine = $engine->forTheme($selectedTheme->packageId);
                }
            }

            (new CustomizationActions())->apply($engine, $input);
            $url = CustomizationUrls::dashboard($selectedTheme);
            return (new Redirect())->redirect($url . '?tab=' . $input->postedTab() . '&saved=1');
        } catch (\InvalidArgumentException|\OutOfBoundsException|\LogicException|\UnexpectedValueException $exception) {
            http_response_code(422);
            return $this->dashboard($exception->getMessage(), $selectedTheme);
        }
    }

    public function preview(array|string $params = []): Response
    {
        try {
            $packageId = CustomizationInput::themeId($params);
            $html = (new PreviewRenderer($this->themeEngine))->render($packageId, $_GET);
            $status = 200;
        } catch (\Throwable $exception) {
            $status = $exception instanceof \OutOfBoundsException ? 404 : 422;
            $html = PreviewRenderer::errorHtml($exception);
        }

        return Response::make($status)
            ->file($html)
            ->asHtml()
            ->header('Cache-Control', 'no-store')
            ->header('Content-Security-Policy', "frame-ancestors 'self'");
    }

    // Keep existing controller actions and route names stable for callers and cached pages.
    public function defaultPreview(array|string $params = []): Response
    {
        return $this->assets()->defaultPreview();
    }

    public function layoutCss(array|string $params = []): Response
    {
        return $this->assets()->layoutCss();
    }

    public function themesCss(array|string $params = []): Response
    {
        return $this->assets()->themesCss();
    }

    public function themesJs(array|string $params = []): Response
    {
        return $this->assets()->themesJs();
    }

    public function previewThemeJs(array|string $params = []): Response
    {
        return $this->assets()->previewThemeJs();
    }

    public function fontAwesomeCss(array|string $params = []): Response
    {
        return $this->assets()->fontAwesomeCss();
    }

    public function fontAwesomeFont(array|string $params = []): Response
    {
        return $this->assets()->fontAwesomeFont($params);
    }

    private function dashboard(?string $error = null, ?ThemeRecord $selectedTheme = null): View
    {
        $input = new CustomizationInput($_GET, $_POST);
        $data = (new CustomizationViewData($this->themeEngine))->build($input->tab(), $selectedTheme);
        return $this->view(array_merge($data, [
            'error' => $error,
            'token' => (new Csrf())->token(),
            'saved' => isset($_GET['saved']),
        ]));
    }

    private function assets(): CustomizationAssets
    {
        return new CustomizationAssets($this->rootDirPath);
    }
}
