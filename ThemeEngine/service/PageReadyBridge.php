<?php

namespace ThemeEngine\service;

use event\EventDispatcher;
use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use ThemeEngine\api\events\PageReadyEvent;
use ThemeEngine\controllers\ThemePage;
use URPC\api\events\BeforeRenderEvent;

/** Bridges URPC's render lifecycle to the public ThemeEngine page-ready event. */
final class PageReadyBridge implements IEventListener
{
    public function __construct(private readonly EventDispatcher $dispatcher) {}

    public function handle(IEvent $event): array
    {
        if (!$event instanceof BeforeRenderEvent || !$event->getURPC() instanceof ThemePage) {
            return [];
        }
        $page = $event->getURPC();
        $ready = new PageReadyEvent($event->getDocument(), $page->pageContext(), $page);
        $this->dispatcher->dispatch($ready);

        // Apply shared browser metadata to the live document after theme listeners have run.
        $document = $ready->getDocument();
        $page->applySiteAppearance($document);
        $event->setDocument($document);
        return [];
    }
}
