<?php

namespace ThemeEngine\service;

use ThemeEngine\api\SectionSource;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemePageContext;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCDocument;
use URPC\api\URPCDocument;
use URPC\service\BlockIdentity;

/** Resolves enabled JSON/provider sections and builds the theme's five-slot document. */
final class ThemeDocumentBuilder
{
    public function __construct(private readonly JsonSourceCache $cache, private readonly ?SidebarWidgetRegistry $widgets = null) {}

    public function build(ThemeDefinition $definition, ThemePageContext $context, array $enabled, array $placements = []): IURPCDocument
    {
        $page = $definition->pages[$context->pageKey];
        $slots = [];
        foreach (ThemeDefinition::SLOTS as $slot) {
            $slots[$slot] = [];
            if (!$enabled[$slot]) {
                continue;
            }

            // A page's explicit null source clears the theme default for this slot.
            $source = array_key_exists($slot, $page->sections)
                ? $page->sections[$slot]
                : ($definition->sections[$slot] ?? null);
            if ($source !== null) {
                $slots[$slot] = $this->renderSection($source, $context, $slot);
            }
            if (isset(SidebarWidgetSettings::SIDEBARS[$slot]) && $this->widgets !== null) {
                $slots[$slot] = array_merge($slots[$slot], $this->widgets->render($placements[$slot] ?? [], $context, $slot));
            }
        }

        return (new LayoutBuilder())->build(
            $slots,
            $enabled,
            $context->theme->name,
            $definition->stylesheets,
            $definition->scripts,
            $page->label . ' · ' . $definition->label,
        );
    }

    private function renderSection(SectionSource $source, ThemePageContext $context, string $slot): array
    {
        $theme = $context->theme;
        if ($source->file !== null) {
            return $this->cache->load(
                ThemeRegistry::resolveFile($theme->root, $source->file),
                $theme->name . '/' . $source->file,
            );
        }

        $blocks = $source->provider->createBlocks($context);
        foreach ($blocks as $block) {
            if (!$block instanceof IURPCBlock) {
                throw new \UnexpectedValueException('Section provider must return URPC blocks.');
            }
        }

        // Preserve explicit source keys; assign stable fallback identities to provider blocks.
        $document = new URPCDocument();
        $document->fromArray([$slot => $blocks]);
        (new BlockIdentity())->assignDocument($document, $theme->name . '/providers/' . $source->key);
        return array_values($document->getSections()[$slot]);
    }
}
