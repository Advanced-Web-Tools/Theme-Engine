<?php

namespace ThemeEngine\service;

use ThemeEngine\service\contracts\IStore;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCDocument;

/** Site-wide browser tab settings applied to the live URPC document. */
final class SiteAppearance
{
    public const FAVICON_PATH = '/theme-engine/favicon';
    public const TITLE_FORMATS = [
        'theme' => 'Theme default',
        'site_pipe_page' => 'Website | Page',
        'page_pipe_site' => 'Page | Website',
        'site_dash_page' => 'Website - Page',
        'page_dash_site' => 'Page - Website',
        'site_dot_page' => 'Website · Page',
        'page_dot_site' => 'Page · Website',
        'site_slash_page' => 'Website / Page',
        'page_slash_site' => 'Page / Website',
        'page' => 'Page only',
        'site' => 'Website only',
    ];

    private const MAX_ICON_BYTES = 512 * 1024;
    private const MAX_ICON_DIMENSION = 512;
    private const ICON_TYPES = [
        IMAGETYPE_ICO => 'image/x-icon',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
        IMAGETYPE_GIF => 'image/gif',
    ];

    public function __construct(private readonly IStore $store) {}

    public function settings(): array
    {
        return $this->store->siteAppearance() + ['title_format' => 'theme', 'favicon' => null];
    }

    public function save(string $format, ?array $upload, bool $removeFavicon): void
    {
        if (!isset(self::TITLE_FORMATS[$format])) {
            throw new \InvalidArgumentException('Choose a valid browser title format.');
        }

        $changes = ['title_format' => $format];
        if ($upload !== null && ($upload['error'] ?? null) !== UPLOAD_ERR_NO_FILE) {
            if ($removeFavicon) {
                throw new \InvalidArgumentException('Choose either to upload a new favicon or to remove the current one.');
            }
            $changes['favicon'] = $this->readUpload($upload);
        } elseif ($removeFavicon) {
            $changes['favicon'] = null;
        }

        $this->store->updateSiteAppearance($changes);
    }

    public static function faviconUrl(?array $favicon): ?string
    {
        return $favicon === null ? null : self::FAVICON_PATH . '?v=' . $favicon['hash'];
    }

    public static function title(string $format, string $page): ?string
    {
        $site = defined('WEB_NAME') ? trim((string) WEB_NAME) : '';
        $page = trim($page);
        return match ($format) {
            'site_pipe_page' => self::joinTitle($site, $page, ' | '),
            'page_pipe_site' => self::joinTitle($page, $site, ' | '),
            'site_dash_page' => self::joinTitle($site, $page, ' - '),
            'page_dash_site' => self::joinTitle($page, $site, ' - '),
            'site_dot_page' => self::joinTitle($site, $page, ' · '),
            'page_dot_site' => self::joinTitle($page, $site, ' · '),
            'site_slash_page' => self::joinTitle($site, $page, ' / '),
            'page_slash_site' => self::joinTitle($page, $site, ' / '),
            'page' => self::joinTitle($page, $site, ''),
            'site' => self::joinTitle($site, $page, ''),
            default => null,
        };
    }

    public function apply(IURPCDocument $document, string $page): void
    {
        $settings = $this->settings();
        $title = self::title($settings['title_format'], $page);
        $favicon = self::faviconUrl($settings['favicon']);
        if ($title === null && $favicon === null) {
            return;
        }

        $head = $this->head($document);
        if ($head === null) {
            return;
        }

        $children = [];
        $hasTitle = false;
        foreach ($head->getBlockChildren() as $child) {
            $tag = strtolower($child->getBlockDescriptor()->getBlockName());
            if ($title !== null && $tag === 'title') {
                if ($hasTitle) {
                    continue;
                }
                $child->setBlockContent($title);
                $child->setBlockChildren([]);
                $child->getBlockDescriptor()->setBlockRenderer('');
                $hasTitle = true;
            }
            if ($favicon !== null && $tag === 'link' && $this->isFavicon($child)) {
                continue;
            }
            $children[] = $child;
        }

        if ($title !== null && !$hasTitle) {
            $children[] = LayoutBuilder::block('title', $title);
        }
        if ($favicon !== null) {
            $children[] = LayoutBuilder::block('link', '', [
                'rel' => 'icon',
                'href' => $favicon,
                'type' => $settings['favicon']['mime'],
            ]);
        }
        $head->setBlockChildren($children);
    }

    private static function joinTitle(string $first, string $second, string $separator): ?string
    {
        if ($first === '') {
            return $second === '' ? null : $second;
        }
        return $second === '' || $separator === '' ? $first : $first . $separator . $second;
    }

    private function head(IURPCDocument $document): ?IURPCBlock
    {
        foreach ($document->getSections() as $blocks) {
            foreach ($blocks as $root) {
                if (strcasecmp($root->getBlockDescriptor()->getBlockName(), 'html') !== 0) {
                    continue;
                }
                foreach ($root->getBlockChildren() as $child) {
                    if (strcasecmp($child->getBlockDescriptor()->getBlockName(), 'head') === 0) {
                        return $child;
                    }
                }
                $head = LayoutBuilder::block('head');
                $root->setBlockChildren([$head, ...$root->getBlockChildren()]);
                return $head;
            }
        }
        return null;
    }

    private function isFavicon(IURPCBlock $link): bool
    {
        $attributes = array_change_key_case($link->getBlockAttributes());
        $rel = $attributes['rel'] ?? '';
        return is_string($rel) && in_array('icon', preg_split('/\s+/', strtolower(trim($rel))), true);
    }

    private function readUpload(array $upload): array
    {
        $error = $upload['error'] ?? null;
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new \InvalidArgumentException('The favicon must be at most 512 KiB.');
        }
        $path = $upload['tmp_name'] ?? null;
        if ($error !== UPLOAD_ERR_OK || !is_string($path) || !is_uploaded_file($path)) {
            throw new \InvalidArgumentException('The favicon upload failed.');
        }
        $contents = file_get_contents($path, false, null, 0, self::MAX_ICON_BYTES + 1);
        if ($contents === false || $contents === '') {
            throw new \InvalidArgumentException('Could not read the uploaded favicon.');
        }
        if (strlen($contents) > self::MAX_ICON_BYTES) {
            throw new \InvalidArgumentException('The favicon must be at most 512 KiB.');
        }

        $image = @getimagesizefromstring($contents);
        if ($image === false || !isset(self::ICON_TYPES[$image[2]])) {
            throw new \InvalidArgumentException('Upload an ICO, PNG, WebP, or GIF image.');
        }
        if ($image[0] < 1 || $image[1] < 1 || max($image[0], $image[1]) > self::MAX_ICON_DIMENSION) {
            throw new \InvalidArgumentException('The favicon must be at most 512 × 512 pixels.');
        }

        return [
            'mime' => self::ICON_TYPES[$image[2]],
            'hash' => hash('sha256', $contents),
            'data' => base64_encode($contents),
        ];
    }
}
