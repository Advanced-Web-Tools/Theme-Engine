<?php

namespace ThemeEngine\controllers;

use ThemeEngine\service\Engine;
use ThemeEngine\service\ThemeDocumentProvider;
use ThemeEngine\api\ThemePageContext;
use URPC\api\contracts\IURPCDocument;
use URPC\service\renderer\URPC;

abstract class ThemePage extends URPC
{
    protected Engine $themeEngine;
    protected string $pageKey;
    protected array $pageData = [];
    private ?ThemeDocumentProvider $themeDocumentProvider = null;

    public function configureTheme(Engine $engine, string $pageKey): void
    {
        $this->themeEngine = $engine;
        $this->pageKey = $pageKey;
        $this->themeDocumentProvider = new ThemeDocumentProvider($engine, $this);
        $this->setDocumentProvider($this->themeDocumentProvider);
        $this->setSourceNamespace($engine->activeTheme()->name . '/pages/' . $pageKey);
    }

    public function render(): string
    {
        try {
            return '<!doctype html>' . parent::render();
        } finally {
            // A cache hit skips createDocument(), so release its source snapshot here too.
            $this->themeDocumentProvider?->clearCacheInputs();
        }
    }

    public function pageContext(): ThemePageContext
    {
        return new ThemePageContext($this->getContext() ?? throw new \LogicException('Page requires AWT Context.'),
            $this->themeEngine->activeTheme(), $this->pageKey, $this->themeEngine->settings(), $this->pageData);
    }

    public function applySiteAppearance(IURPCDocument $document): void
    {
        $this->themeEngine->appearance->apply($document, $this->themeEngine->definition()->pages[$this->pageKey]->label);
    }

    public function index(array|string $params = []): self
    {
        $this->pageData = ['params' => $params];
        return $this;
    }
}
