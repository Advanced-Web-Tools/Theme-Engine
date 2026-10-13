<?php

namespace ThemeEngine\controllers;

use ThemeEngine\service\Engine;
use ThemeEngine\service\ThemeDocumentProvider;
use ThemeEngine\api\ThemePageContext;
use URPC\service\renderer\URPC;

abstract class ThemePage extends URPC
{
    protected Engine $themeEngine;
    protected string $pageKey;
    protected array $pageData = [];

    public function configureTheme(Engine $engine, string $pageKey): void
    {
        $this->themeEngine = $engine;
        $this->pageKey = $pageKey;
        $this->setDocumentProvider(new ThemeDocumentProvider($engine, $this));
        $this->setSourceNamespace($engine->activeTheme()->name . '/pages/' . $pageKey);
    }

    public function render(): string
    {
        return '<!doctype html>' . parent::render();
    }

    public function pageContext(): ThemePageContext
    {
        return new ThemePageContext($this->getContext() ?? throw new \LogicException('Page requires AWT Context.'),
            $this->themeEngine->activeTheme(), $this->pageKey, $this->themeEngine->settings(), $this->pageData);
    }

    public function index(array|string $params = []): self
    {
        $this->pageData = ['params' => $params];
        return $this;
    }
}
