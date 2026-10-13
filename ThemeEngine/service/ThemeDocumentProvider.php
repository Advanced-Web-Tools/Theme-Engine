<?php

namespace ThemeEngine\service;

use context\Context;
use ThemeEngine\controllers\ThemePage;
use URPC\api\contracts\IURPCDocument;
use URPC\service\contracts\provider\IURPCDocumentProvider;

final class ThemeDocumentProvider implements IURPCDocumentProvider
{
    public function __construct(
        private readonly Engine $engine,
        private readonly ThemePage $page,
    ) {}

    public function createDocument(Context $context): IURPCDocument
    {
        return $this->engine->createDocument($this->page->pageContext());
    }
}
