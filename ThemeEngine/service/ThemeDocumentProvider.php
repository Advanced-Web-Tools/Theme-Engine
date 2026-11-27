<?php

namespace ThemeEngine\service;

use context\Context;
use ThemeEngine\controllers\ThemePage;
use URPC\api\contracts\IURPCDocument;
use URPC\service\contracts\provider\ICacheableURPCDocumentProvider;

final class ThemeDocumentProvider implements ICacheableURPCDocumentProvider
{
    private ?array $cacheInputs = null;

    public function __construct(
        private readonly Engine $engine,
        private readonly ThemePage $page,
    ) {}

    public function getDocumentCacheRevision(Context $context): ?string
    {
        $this->cacheInputs = $this->engine->prepareDocumentCache($this->page->pageContext());
        return $this->cacheInputs['revision'] ?? null;
    }

    public function createDocument(Context $context): IURPCDocument
    {
        $inputs = $this->cacheInputs;
        $this->clearCacheInputs();
        return $this->engine->createDocument($inputs['context'] ?? $this->page->pageContext(), $inputs);
    }

    public function clearCacheInputs(): void
    {
        $this->cacheInputs = null;
    }
}
