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
    // Bump when changes to the builder or layout affect the prepared tree.
    private const CACHE_REVISION_VERSION = 1;

    public function __construct(private readonly JsonSourceCache $cache, private readonly ?SidebarWidgetRegistry $widgets = null) {}

    /**
     * Capture the exact files and layout a cache miss will build from.
     * @return array{revision: string, context: ThemePageContext, enabled: array, files: array}|null
     */
    public function cacheInputs(ThemeDefinition $definition, ThemePageContext $context, array $enabled, array $placements): ?array
    {
        $page = $definition->pages[$context->pageKey];
        $sources = [];
        $files = [];
        $providerHashes = [];
        foreach ($this->enabledSources($definition, $context, $enabled) as $slot => $source) {
            // Sidebar widgets can embed request data while building their blocks.
            if ($this->widgets !== null && isset(SidebarWidgetSettings::SIDEBARS[$slot])) {
                if (($placements[$slot] ?? []) !== []) {
                    return null;
                }
                // Preserve sidebar registration when the cache skips building an empty sidebar.
                $this->widgets->all();
            }
            if ($source === null) {
                continue;
            }
            if ($source->provider !== null) {
                if (!$source->static) {
                    return null;
                }
                $class = $source->provider::class;
                if (!isset($providerHashes[$class])) {
                    $file = (new \ReflectionClass($source->provider))->getFileName();
                    $hash = $file === false ? false : @hash_file('sha256', $file);
                    if ($hash === false) {
                        return null;
                    }
                    $providerHashes[$class] = $hash;
                }
                $sources[$slot] = [$source->key, $class, $providerHashes[$class]];
                continue;
            }
            try {
                $path = ThemeRegistry::resolveFile($context->theme->root, $source->file);
            } catch (\InvalidArgumentException) {
                return null;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                return null;
            }
            $files[$slot] = ['path' => $path, 'content' => $content];
            $sources[$slot] = [$source->file, hash('sha256', $content)];
        }

        $revision = hash('sha256', serialize([
            self::CACHE_REVISION_VERSION,
            $context->theme->packageId,
            $context->theme->name,
            $context->theme->version,
            $page->key,
            $page->label,
            $definition->label,
            $definition->stylesheets,
            $definition->scripts,
            $context->settings,
            $enabled,
            $sources,
        ]));
        return ['revision' => $revision, 'context' => $context, 'enabled' => $enabled, 'files' => $files];
    }

    public function build(ThemeDefinition $definition, ThemePageContext $context, array $enabled, array $placements = [], array $files = []): IURPCDocument
    {
        $page = $definition->pages[$context->pageKey];
        $slots = array_fill_keys(ThemeDefinition::SLOTS, []);
        foreach ($this->enabledSources($definition, $context, $enabled) as $slot => $source) {
            if ($source !== null) {
                $slots[$slot] = $this->renderSection($source, $context, $slot, $files[$slot] ?? null);
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

    private function enabledSources(ThemeDefinition $definition, ThemePageContext $context, array $enabled): iterable
    {
        $page = $definition->pages[$context->pageKey];
        foreach (ThemeDefinition::SLOTS as $slot) {
            if ($enabled[$slot]) {
                // A page's explicit null source clears the theme default for this slot.
                yield $slot => array_key_exists($slot, $page->sections)
                    ? $page->sections[$slot]
                    : ($definition->sections[$slot] ?? null);
            }
        }
    }

    private function renderSection(SectionSource $source, ThemePageContext $context, string $slot, ?array $file): array
    {
        $theme = $context->theme;
        if ($source->file !== null) {
            return $this->cache->load(
                $file['path'] ?? ThemeRegistry::resolveFile($theme->root, $source->file),
                $theme->name . '/' . $source->file,
                $file['content'] ?? null,
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
