<?php

namespace ThemeEngine\api\events;

use event\interfaces\IEvent;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\controllers\ThemePage;
use ThemeEngine\service\LayoutBuilder;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCDocument;

/** Fired after layout/IDs are prepared and before block processors render the page. */
final class PageReadyEvent implements IEvent
{
    public const NAME = 'theme_engine.page.ready';

    public function __construct(
        private IURPCDocument $document,
        public readonly ThemePageContext $context,
        public readonly ThemePage $controller,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDocument(): IURPCDocument
    {
        return $this->document;
    }

    public function setDocument(IURPCDocument $document): void
    {
        $this->document = $document;
    }

    /** Returns the live slot wrapper, or null when disabled. Children already have IDs. */
    public function getSection(string $name): ?IURPCBlock
    {
        return LayoutBuilder::findSlot($this->document, $name);
    }

    public function bundle(): array
    {
        return [
            'document' => $this->document,
            'context' => $this->context,
            'controller' => $this->controller,
        ];
    }
}
