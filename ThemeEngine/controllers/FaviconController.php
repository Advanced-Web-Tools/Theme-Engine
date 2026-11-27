<?php

namespace ThemeEngine\controllers;

use controller\Controller;
use response\Response;
use ThemeEngine\service\SiteAppearance;

final class FaviconController extends Controller
{
    public function __construct(private readonly SiteAppearance $appearance) {}

    public function index(array|string $params = []): Response
    {
        $favicon = $this->appearance->settings()['favicon'];
        $contents = $favicon === null ? false : base64_decode($favicon['data'], true);
        if ($contents === false) {
            return Response::make(404)->asText()->header('Cache-Control', 'no-store');
        }

        // AWT's text sender supports inline bytes; its image sender requires a disk path.
        return Response::make()->file($contents)->asText()
            ->header('Content-Type', $favicon['mime'])
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'no-cache');
    }
}
