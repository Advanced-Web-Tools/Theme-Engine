<?php

namespace ThemeEngine\service;

use DOMDocument;
use router\matching\SegmentPathMatcher;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\service\customization\CustomizationUrls;

/** Rewrites links to the selected theme's pages so they stay inside preview mode. */
final class ThemePreviewNavigation
{
    public function rewrite(string $html, int $packageId, ThemeDefinition $definition, string $pagePath, string $siteUrl): string
    {
        $document = $this->parseDocument($html);
        $matcher = new SegmentPathMatcher();
        foreach ($document->getElementsByTagName('a') as $link) {
            if (!$link->hasAttribute('href') || $link->hasAttribute('download')) {
                continue;
            }
            $url = $this->previewUrl($link->getAttribute('href'), $packageId, $definition, $pagePath, $siteUrl, $matcher);
            if ($url !== null) {
                $link->setAttribute('href', $url);
                $link->setAttribute('target', '_self');
            }
        }
        return preg_replace('/\A<!DOCTYPE html>/i', '<!doctype html>', $document->saveHTML());
    }

    private function parseDocument(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            // A non-ASCII title before the charset meta can make libxml assume Latin-1.
            $document->loadHTML('<?xml encoding="UTF-8">' . $html);
            foreach (iterator_to_array($document->childNodes) as $node) {
                if ($node->nodeType === XML_PI_NODE) {
                    $document->removeChild($node);
                }
            }
            $document->encoding = 'UTF-8';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return $document;
    }

    private function previewUrl(string $href, int $packageId, ThemeDefinition $definition, string $pagePath, string $siteUrl, SegmentPathMatcher $matcher): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }
        $url = parse_url($href);
        if ($url === false || isset($url['user']) || isset($url['pass'])) {
            return null;
        }
        if (isset($url['scheme']) && !in_array(strtolower($url['scheme']), ['http', 'https'], true)) {
            return null;
        }
        if (isset($url['host']) && !$this->sameOrigin($url, $siteUrl)) {
            return null;
        }

        $path = $url['path'] ?? '';
        if ($path === '') {
            $path = $pagePath;
        } elseif (!str_starts_with($path, '/')) {
            $path = substr($pagePath, 0, strrpos($pagePath, '/') + 1) . $path;
        }
        $path = $this->normalizePath($path);
        foreach ($definition->routes as $index => $route) {
            $params = $matcher->match($route->path, $path);
            if ($params === null) {
                continue;
            }
            parse_str($url['query'] ?? '', $query);
            // Destination routing fields replace any fields carried in the original URL.
            unset($query['route'], $query['params']);
            $query['route'] = $index;
            if ($params !== []) {
                $query['params'] = array_map('rawurldecode', $params);
            }
            $previewUrl = CustomizationUrls::preview($packageId, $query);
            if (isset($url['fragment'])) {
                $previewUrl .= '#' . $url['fragment'];
            }
            return $previewUrl;
        }
        return null;
    }

    private function sameOrigin(array $url, string $siteUrl): bool
    {
        $site = parse_url($siteUrl);
        if ($site === false || !isset($site['host'])) {
            return false;
        }
        $scheme = strtolower($url['scheme'] ?? $site['scheme'] ?? 'http');
        $siteScheme = strtolower($site['scheme'] ?? 'http');
        return strtolower($url['host']) === strtolower($site['host']) && $scheme === $siteScheme
            && ($url['port'] ?? ($scheme === 'https' ? 443 : 80)) === ($site['port'] ?? ($siteScheme === 'https' ? 443 : 80));
    }

    private function normalizePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                if (count($segments) > 1) {
                    array_pop($segments);
                }
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }
        return implode('/', $segments) ?: '/';
    }
}
