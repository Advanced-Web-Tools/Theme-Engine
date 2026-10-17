<?php

// Run with: php tests/layout-cache.php
require_once __DIR__ . '/bootstrap.php';

use ThemeEngine\api\ThemePageContext;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\service\contracts\IStore;
use ThemeEngine\service\Engine;
use ThemeEngine\service\JsonSourceCache;
use ThemeEngine\service\LayoutBuilder;
use ThemeEngine\service\ThemeRegistry;
use ThemeEngine\service\preview\PreviewSettings;

$directory = sys_get_temp_dir() . '/themeengine-layout-' . bin2hex(random_bytes(8));
mkdir($directory);
mkdir($directory . '/cache');

$store = new class implements IStore {
    public array $configuration = ['settings' => [], 'pages' => []];
    public array $snapshots = [];
    public bool $failSave = false;
    public function state(): array { return ['active_theme_package_id' => 1]; }
    public function saveState(array $state): void {}
    public function config(int $packageId): array { return $this->configuration; }
    public function saveConfig(int $packageId, array $config): void {
        if ($this->failSave) throw new RuntimeException('Simulated failed save');
        $this->configuration = $config;
    }
    public function presets(int $packageId): array { return $this->snapshots; }
    public function savePreset(int $packageId, string $name, array $payload): int {
        $this->snapshots[] = ['id' => 1, 'name' => $name, 'payload' => $payload];
        return 1;
    }
    public function deletePreset(int $packageId, int $id): void {}
    public function transaction(callable $callback): mixed { return $callback(); }
};

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

try {
    file_put_contents($directory . '/header.json', '{"blocks":[{"descriptor":{"name":"h1"},"content":"Header restored"}]}');
    file_put_contents($directory . '/theme.php', <<<'PHP'
<?php
return new class implements \ThemeEngine\api\contracts\ITheme {
    public function getDefinition(): \ThemeEngine\api\ThemeDefinition {
        return new \ThemeEngine\api\ThemeDefinition('Test', [
            'home' => new \ThemeEngine\api\PageDefinition('home', 'Home'),
        ], [], ['header' => \ThemeEngine\api\SectionSource::json('header.json')]);
    }
};
PHP);
    $registry = new ThemeRegistry();
    $theme = new ThemeRecord(1, 'Test', $directory, '1.0.0', true);
    $registry->register($theme);
    $cache = new JsonSourceCache($directory . '/cache');
    $engine = new Engine($registry, $store, $cache, new \event\EventDispatcher());
    $engine->boot();
    $preview = new PreviewSettings($engine, dirname(__DIR__) . '/ThemeEngine/views');
    // The editor waits for save, then sends a separate cache_clear POST.
    $saveAndClear = static function (array $post) use ($preview): void {
        $preview->save(1, $post);
        $preview->save(1, ['operation' => 'cache_clear']);
    };
    $context = new ThemePageContext(new \context\Context($directory, 'Test', '1'), $theme, 'home', [], []);
    $header = static fn() => LayoutBuilder::findSlot($engine->createDocument($context), 'header');
    $assertHeader = static function () use ($header): void {
        $block = $header();
        check($block !== null, 'Header slot missing');
        $children = array_values($block->getBlockChildren());
        check(count($children) === 1 && $children[0]->getBlockContent() === 'Header restored', 'Cached header content missing');
    };

    $assertHeader(); // Populate both memory and disk caches.
    $saveAndClear(['operation' => 'layout', 'sections' => ['header' => '0', 'footer' => '1']]);
    check($header() === null, 'Header must disappear after disabling it');

    // Simulate a stale normalized source left by an earlier render or package version.
    $cacheFile = $directory . '/cache/' . hash('sha256', 'v1|' . $directory . '/header.json|Test/header.json') . '.json';
    $seedStaleCache = static function () use ($cacheFile, $directory): void {
        file_put_contents($cacheFile, json_encode([
            'hash' => hash_file('sha256', $directory . '/header.json'), 'blocks' => [],
        ], JSON_THROW_ON_ERROR));
    };
    $seedStaleCache();
    $saveAndClear(['operation' => 'layout', 'sections' => ['header' => '1', 'footer' => '1']]);
    check(!is_file($cacheFile), 'Clear cache after global layout save must remove stale disk entries');
    $assertHeader();

    $saveAndClear(['operation' => 'page', 'page_key' => 'home', 'sections' => ['header' => 'off']]);
    check($header() === null, 'Page override must hide the header');
    $seedStaleCache();
    $saveAndClear(['operation' => 'page', 'page_key' => 'home', 'sections' => ['header' => 'on']]);
    check(!is_file($cacheFile), 'Clear cache after page layout save must remove stale entries');
    $assertHeader();

    $engine->savePreset('Header visible');
    $saveAndClear(['operation' => 'page', 'page_key' => 'home', 'sections' => ['header' => 'off']]);
    $seedStaleCache();
    $saveAndClear(['operation' => 'preset_apply', 'preset_id' => '1']);
    check(!is_file($cacheFile), 'Clear cache after preset application must remove stale entries');
    $assertHeader();

    $seedStaleCache();
    $saveAndClear(['operation' => 'settings', 'settings' => []]);
    check(!is_file($cacheFile), 'Clear cache after settings save must remove stale entries');
    $assertHeader();
    $seedStaleCache();
    $saveAndClear(['operation' => 'reset_settings']);
    check(!is_file($cacheFile), 'Clear cache after restoring defaults must remove stale entries');
    $assertHeader();

    $seedStaleCache();
    $store->failSave = true;
    try {
        $saveAndClear(['operation' => 'layout', 'sections' => ['header' => '0']]);
        throw new RuntimeException('Expected save failure');
    } catch (RuntimeException $exception) {
        check($exception->getMessage() === 'Simulated failed save', 'Unexpected save failure');
    }
    check(is_file($cacheFile), 'Failed saves must not invalidate cache');
    echo "PASS: save followed by the explicit Clear cache action restores headers; failed saves skip clearing.\n";
} finally {
    foreach (glob($directory . '/cache/*') ?: [] as $file) unlink($file);
    rmdir($directory . '/cache');
    foreach (glob($directory . '/*') ?: [] as $file) unlink($file);
    rmdir($directory);
}
