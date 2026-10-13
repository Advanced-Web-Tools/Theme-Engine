<?php

namespace ThemeEngine\service;

use ThemeEngine\api\ThemeDefinition;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCDocument;
use URPC\api\URPCBlock;
use URPC\api\URPCBlockDescriptor;
use URPC\api\URPCDocument;

/** Builds the fixed five-slot HTML layout and locates its live logical sections. */
final class LayoutBuilder
{
    public function build(array $slots, array $enabled, string $namespace, array $stylesheets, array $scripts, string $title = ''): IURPCDocument
    {
        $row = [];
        foreach (['side_l', 'content', 'side_r'] as $slot) {
            if ($enabled[$slot] ?? false) {
                $row[] = $this->slot($slot, $slots[$slot], $namespace);
            }
        }
        $body = [];
        if ($enabled['header']) {
            $body[] = $this->slot('header', $slots['header'], $namespace);
        }
        $body[] = self::block('div', '', ['class' => 'te-row'], $row, $namespace . '/layout/row');
        if ($enabled['footer']) {
            $body[] = $this->slot('footer', $slots['footer'], $namespace);
        }

        // Public pages must load base styles without constructing an authenticated dashboard controller.
        $stylesheets = array_unique(array_merge(['/awt_packages/ThemeEngine/assets/layout.css'], $stylesheets));
        $head = [
            self::block('meta', '', ['charset' => 'utf-8'], [], $namespace . '/layout/meta'),
            self::block('title', $title, [], [], $namespace . '/layout/title'),
            self::block('meta', '', ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1'], [], $namespace . '/layout/viewport'),
        ];
        foreach ($stylesheets as $url) {
            $head[] = self::block('link', '', ['rel' => 'stylesheet', 'href' => $url], [], $namespace . '/layout/asset');
        }
        foreach (array_unique($scripts) as $url) {
            $body[] = self::block('script', '', ['src' => $url, 'defer' => true], [], $namespace . '/layout/script');
        }

        $root = self::block('html', '', ['lang' => 'en'], [
            self::block('head', '', [], $head, $namespace . '/layout/head'),
            self::block('body', '', ['class' => 'te-page'], $body, $namespace . '/layout/body'),
        ], $namespace . '/layout');
        $document = new URPCDocument();
        $document->fromArray(['layout' => [$root]]);
        return $document;
    }

    public static function block(string $tag, string $text = '', array $attrs = [], array $children = [], string $source = ''): IURPCBlock
    {
        $block = new URPCBlock();
        $block->setBlockDescriptor(new URPCBlockDescriptor($tag, [], '', $source));
        $block->setBlockContent($text);
        $block->setBlockAttributes($attrs);
        $block->setBlockChildren($children);
        return $block;
    }

    public static function findSlot(IURPCDocument $document, string $name): ?IURPCBlock
    {
        if (!in_array($name, ThemeDefinition::SLOTS, true)) {
            throw new \InvalidArgumentException('Unknown section.');
        }
        $seen = new \SplObjectStorage();
        $find = function (IURPCBlock $block) use (&$find, $seen, $name): ?IURPCBlock {
            if ($seen->contains($block)) {
                return null;
            }
            $seen->attach($block);
            if (($block->getBlockAttributes()['data-te-section'] ?? null) === $name) {
                return $block;
            }
            foreach ($block->getBlockChildren() as $child) {
                if ($match = $find($child)) {
                    return $match;
                }
            }
            return null;
        };
        foreach ($document->getSections() as $blocks) {
            foreach ($blocks as $block) {
                if ($match = $find($block)) {
                    return $match;
                }
            }
        }
        return null;
    }

    private function slot(string $name, array $blocks, string $namespace): IURPCBlock
    {
        $tag = match ($name) {
            'header' => 'header',
            'footer' => 'footer',
            'content' => 'main',
            default => 'aside',
        };
        return self::block(
            $tag,
            '',
            ['class' => 'te-section te-' . $name, 'data-te-section' => $name],
            $blocks,
            $namespace . '/slot/' . $name,
        );
    }
}
