<?php

namespace ThemeEngine\service;

use URPC\api\contracts\IURPCBlock;
use URPC\api\URPCBlock;
use URPC\api\URPCBlockDescriptor;
use URPC\service\contracts\loader\IURPCLoaderProvider;
use URPC\service\loader\URPCLoader;

/** Content-hashed JSON source cache; every load returns freshly hydrated blocks. */
final class JsonSourceCache
{
    private array $memory = [];

    public function __construct(private readonly string $directory) {}

    public function load(string $file, string $sourceKey): array
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException('Cannot read section source.');
        }
        $hash = hash('sha256', $content);
        $key = hash('sha256', 'v1|' . $file . '|' . $sourceKey);
        $target = $this->directory . '/' . $key . '.json';
        $cached = $this->memory[$key] ?? null;
        if ($cached === null && is_file($target)) {
            try {
                $cached = json_decode((string) file_get_contents($target), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                $cached = null;
            }
        }
        if (is_array($cached) && ($cached['hash'] ?? '') === $hash && is_array($cached['blocks'] ?? null)) {
            try {
                return array_map($this->hydrate(...), $cached['blocks']);
            } catch (\Throwable) {
                // Rebuild corrupt cached blocks from the original source.
            }
        }

        $provider = new class($content, $sourceKey) implements IURPCLoaderProvider {
            public function __construct(private string $content, private string $key) {}

            public function getContent(): string
            {
                return $this->content;
            }

            public function getSourceKey(): string
            {
                return $this->key;
            }
        };
        $blocks = (new URPCLoader())->load($provider);
        $payload = ['hash' => $hash, 'blocks' => array_map($this->export(...), $blocks)];
        $this->memory[$key] = $payload;
        $this->writeCache($target, $payload);
        return $blocks;
    }

    public function clear(): void
    {
        $this->memory = [];
        foreach (glob($this->directory . '/*.json') ?: [] as $file) {
            if (!@unlink($file)) {
                throw new \RuntimeException('Cannot remove a source cache entry.');
            }
        }
    }

    private function writeCache(string $target, array $payload): void
    {
        // Disk caching is optional: an unwritable directory must not prevent rendering.
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true)) {
            return;
        }
        $temporaryFile = @tempnam($this->directory, '.te-');
        if ($temporaryFile === false) {
            return;
        }
        try {
            if (@file_put_contents($temporaryFile, json_encode($payload, JSON_THROW_ON_ERROR), LOCK_EX) !== false) {
                @rename($temporaryFile, $target);
            }
        } finally {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }
        }
    }

    private function export(IURPCBlock $block): array
    {
        $descriptor = $block->getBlockDescriptor();
        return [
            'name' => $descriptor->getBlockName(),
            'props' => $descriptor->getBlockProps(),
            'renderer' => $descriptor->getBlockRenderer(),
            'source' => $descriptor->getSourceKey(),
            'uid' => $descriptor->getBlockUid(),
            'attrs' => $block->getBlockAttributes(),
            'text' => $block->getBlockContent(),
            'children' => array_map($this->export(...), $block->getBlockChildren()),
        ];
    }

    private function hydrate(array $data): IURPCBlock
    {
        $descriptor = new URPCBlockDescriptor($data['name'], $data['props'], $data['renderer'], $data['source']);
        $descriptor->setBlockUid($data['uid']);
        $block = new URPCBlock();
        $block->setBlockDescriptor($descriptor);
        $block->setBlockContent($data['text']);
        $block->setBlockAttributes($data['attrs']);
        $block->setBlockChildren(array_map($this->hydrate(...), $data['children']));
        return $block;
    }
}
