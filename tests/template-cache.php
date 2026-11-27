<?php

// Run with: php tests/template-cache.php
use context\Context;
use event\EventDispatcher;
use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use object\ObjectFactory;
use router\security\Csrf;
use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\api\contracts\ITheme;
use ThemeEngine\api\events\PageReadyEvent;
use ThemeEngine\api\PageDefinition;
use ThemeEngine\api\RouteDefinition;
use ThemeEngine\api\SectionSource;
use ThemeEngine\api\SidebarWidgetDefinition;
use ThemeEngine\api\ThemeDefinition;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\controllers\ThemePage;
use ThemeEngine\service\contracts\IStore;
use ThemeEngine\service\Engine;
use ThemeEngine\service\JsonSourceCache;
use ThemeEngine\service\LayoutBuilder;
use ThemeEngine\service\PageReadyBridge;
use ThemeEngine\service\ThemeDocumentProvider;
use ThemeEngine\service\ThemePreview;
use ThemeEngine\service\ThemeRegistry;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCBlockRenderer;
use URPC\api\contracts\IURPCDocument;
use URPC\api\events\BeforeRenderEvent;
use URPC\api\events\InvalidateTemplateCacheEvent;
use URPC\service\renderer\TemplateCacheInvalidationListener;
use URPC\service\renderer\URPC;
use vfs\cache\Cache;

$framework = getenv('AWT_FRAMEWORK_CLASSES') ?: dirname(__DIR__, 3) . '/Advanced-Web-Tools-Framework/awt_src/classes';
$urpc = getenv('URPC_PACKAGE_ROOT') ?: dirname(__DIR__, 2) . '/URPC/URPC';
spl_autoload_register(static function (string $class) use ($framework, $urpc): void {
    $path = str_replace('\\', '/', $class) . '.php';
    $file = match (true) {
        str_starts_with($path, 'ThemeEngine/') => dirname(__DIR__) . '/' . $path,
        str_starts_with($path, 'URPC/') => $urpc . '/' . substr($path, 5),
        default => $framework . '/' . $path,
    };
    if (is_file($file)) {
        require $file;
    }
});

final class TemplateTestPage extends ThemePage {}

final class TemplateTestPreparedPage extends ThemePage
{
    protected function prepareDocument(IURPCDocument $document): IURPCDocument
    {
        $slot = LayoutBuilder::findSlot($document, 'content');
        $slot->addBlockChild(LayoutBuilder::block('span', 'prepared-' . $this->pageData['params']['item']));
        return $document;
    }
}

final class TemplateTestTheme implements ITheme
{
    public function getDefinition(): ThemeDefinition
    {
        $provider = new class implements ISectionProvider {
            public function createBlocks(ThemePageContext $context): array
            {
                return [LayoutBuilder::block('p', $context->data['params']['item'])];
            }
        };
        return new ThemeDefinition('Fixture', [
            'home' => new PageDefinition('home', 'Home', ['content' => SectionSource::json('home.json')]),
            'provided' => new PageDefinition('provided', 'Provided', ['content' => SectionSource::provider($provider, 'provided')]),
        ], [
            new RouteDefinition('/{item}', 'home', (new ObjectFactory())->setClassName(TemplateTestPage::class)),
        ], [
            'header' => SectionSource::json('header.json'),
        ], [
            'color' => ['type' => 'color', 'label' => 'Color', 'default' => '#000000'],
        ]);
    }
}

final class TemplateTestForm implements IURPCBlockRenderer
{
    public function process(IURPCBlock $block, URPC $urpc): void
    {
        $block->setBlockContent($_SESSION['receipt']);
        $block->setBlockChildren([
            LayoutBuilder::block('input', '', ['name' => 'form', 'value' => $urpc->pageContext()->data['params']['item']]),
            LayoutBuilder::block('input', '', ['name' => '_token', 'value' => (new Csrf())->token()]),
        ]);
    }
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$work = sys_get_temp_dir() . '/theme-engine-cache-' . bin2hex(random_bytes(8));
mkdir($work, 0700);
define('DATA', $work . '/');
session_start(['save_path' => $work, 'use_cookies' => 0, 'cache_limiter' => '']);
try {
    file_put_contents($work . '/theme.php', '<?php return new TemplateTestTheme();');
    $writeSource = static function (string $file, string $text) use ($work): void {
        $blocks = [['descriptor' => ['name' => 'p'], 'content' => $text]];
        if ($file === 'home') {
            $blocks[] = ['descriptor' => ['name' => 'form', 'renderer' => TemplateTestForm::class]];
        }
        file_put_contents($work . '/' . $file . '.json', json_encode(['blocks' => $blocks], JSON_THROW_ON_ERROR));
    };
    $writeSource('home', 'first-source');
    $writeSource('header', 'first-header');

    $store = new class implements IStore {
        private array $config = ['settings' => []];
        public function state(): array { return ['active_theme_package_id' => 1]; }
        public function saveState(array $state): void {}
        public function config(int $packageId): array { return $this->config; }
        public function saveConfig(int $packageId, array $config): void { $this->config = $config; }
        public function presets(int $packageId): array { return []; }
        public function savePreset(int $packageId, string $name, array $payload): int { throw new LogicException('Not used.'); }
        public function deletePreset(int $packageId, int $id): void {}
        public function transaction(callable $callback): mixed { return $callback(); }
    };
    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->addListener(BeforeRenderEvent::NAME, new PageReadyBridge($eventDispatcher));
    $eventDispatcher->addListener(InvalidateTemplateCacheEvent::NAME, new TemplateCacheInvalidationListener());
    $eventDispatcher->addListener(PageReadyEvent::NAME, new class implements IEventListener {
        public function handle(IEvent $event): array
        {
            $slot = $event->getSection('content');
            $slot->addBlockChild(LayoutBuilder::block('span', 'ready-' . $event->context->data['params']['item']));
            return [];
        }
    });

    $registry = new ThemeRegistry();
    $registry->register(new ThemeRecord(1, 'Fixture', $work, '1.0.0', true));
    $cache = new JsonSourceCache($work . '/sources');
    $engine = new Engine($registry, $store, $cache, $eventDispatcher);
    $engine->boot();

    $context = new Context($work . '/', 'Fixture', '1');
    $newPage = static function (string $key = 'home') use ($context, $engine): TemplateTestPage {
        $page = new TemplateTestPage();
        $page->configureTheme($engine, $key);
        $page->setContext($context);
        return $page;
    };
    $namespace = 'Fixture/pages/home';
    $entry = static fn () => Cache::get('urpc-templates', $namespace);
    $sourceWasLoaded = static fn () => glob($work . '/sources/*.json') !== [];

    $page = $newPage();
    $_SESSION['receipt'] = 'first-receipt';
    $firstToken = (new Csrf())->regenerate();
    $first = $page->index(['item' => 'first-form'])->render();
    check($sourceWasLoaded() && is_array($entry()), 'A cold render builds and writes the prepared tree.');
    check(is_file(DATA . 'storage/framework/cache/urpc-templates/' . hash('sha256', $namespace) . '.php'), 'The tree is persisted in the VFS pool.');
    foreach (['first-source', 'first-header', 'first-receipt', 'first-form', 'ready-first-form', $firstToken] as $value) {
        check(str_contains($first, $value), 'The first render includes ' . $value);
    }
    $cached = serialize($entry());
    foreach (['first-receipt', 'first-form', 'ready-first-form', $firstToken] as $value) {
        check(!str_contains($cached, $value), 'Request data leaked into the prepared tree.');
    }

    // Loading a JSON source recreates its normalized cache file. A prepared hit skips that path.
    $cache->clear();
    $_SESSION['receipt'] = 'second-receipt';
    $secondToken = (new Csrf())->regenerate();
    $page = $newPage();
    $second = $page->index(['item' => 'second-form'])->render();
    check(!$sourceWasLoaded() && serialize($entry()) === $cached, 'A warm render skips document construction.');
    foreach (['second-receipt', 'second-form', 'ready-second-form', $secondToken] as $value) {
        check(str_contains($second, $value), 'A hit still renders fresh ' . $value);
    }
    check(!str_contains($second, $firstToken) && !str_contains($second, 'first-form'), 'The hit has no previous request values.');

    $writeSource('header', 'other-header');
    check(str_contains($page->render(), 'other-header') && $sourceWasLoaded(), 'Changing shared JSON rebuilds the tree.');
    $writeSource('home', 'other-source');
    check(str_contains($page->render(), 'other-source'), 'Changing page JSON rebuilds the tree.');

    $engine->savePageSections('home', ['header' => 'off']);
    check(!str_contains($page->render(), 'data-te-section="header"'), 'Changing the effective layout rebuilds the tree.');
    $signature = $entry()['signature'];
    $engine->saveSettings(['color' => '#aaaaaa']);
    $page->render();
    check($entry()['signature'] !== $signature, 'Changing saved settings changes the revision.');

    $cache->clear();
    $eventDispatcher->dispatch(new InvalidateTemplateCacheEvent($namespace));
    check($entry() === false, 'Invalidating the source namespace removes the VFS entry.');
    $page->render();
    check($sourceWasLoaded() && is_array($entry()), 'The next render rebuilds after invalidation.');

    $provided = $newPage('provided');
    check(str_contains($provided->index(['item' => 'provider-first'])->render(), '<p>provider-first</p>'), 'PHP sections use request data.');
    check(str_contains($provided->index(['item' => 'provider-second'])->render(), '<p>provider-second</p>'), 'PHP sections run on each render.');
    check(Cache::get('urpc-templates', 'Fixture/pages/provided') === false, 'PHP section output stays outside the VFS cache.');

    $cache->clear();
    $cached = serialize($entry());
    $preview = (new ThemePreview($engine))->render(1, 0, ['item' => 'preview-form']);
    check($sourceWasLoaded() && serialize($entry()) === $cached, 'Preview builds its own tree without touching the public cache.');
    check(str_contains($preview, 'ready-preview-form'), 'Preview retains the page-ready lifecycle.');

    $custom = new TemplateTestPreparedPage();
    $custom->configureTheme($engine, 'home');
    $custom->setContext($context);
    $cache->clear();
    check(str_contains($custom->index(['item' => 'custom-form'])->render(), 'prepared-custom-form'), 'A custom preparation hook still runs.');
    check($sourceWasLoaded() && serialize($entry()) === $cached, 'Custom preparation bypasses the default prepared tree.');

    $engine->widgets->register(new SidebarWidgetDefinition('fixture/request', 'Request', new class implements ISectionProvider {
        public function createBlocks(ThemePageContext $context): array
        {
            return [LayoutBuilder::block('p', 'widget-' . $context->data['params']['item'])];
        }
    }));
    $engine->saveGlobalWidgets(['side_l' => ['fixture/request']]);
    $cache->clear();
    $page->render();
    check(!$sourceWasLoaded(), 'Widgets in a hidden sidebar still allow a VFS hit.');
    $engine->savePageSections('home', ['header' => 'off', 'side_l' => 'on']);
    check(str_contains($page->render(), 'widget-second-form'), 'Enabling a placed widget uses the request build path.');
    check(str_contains($page->index(['item' => 'third-form'])->render(), 'widget-third-form'), 'The sidebar widget receives fresh request data.');
    check(serialize($entry()) === $cached, 'The widget output stays outside the VFS cache.');
    $engine->savePageSections('home', ['header' => 'off']);

    $provider = new ThemeDocumentProvider($engine, $page);
    check($provider->getDocumentCacheRevision($context) !== null, 'Public JSON has an available revision.');
    $writeSource('home', 'latest-source');
    $document = $provider->createDocument($context);
    $blocks = array_values(LayoutBuilder::findSlot($document, 'content')->getBlockChildren());
    check($blocks[0]->getBlockContent() === 'other-source', 'A miss builds from the exact source used to calculate the revision.');
    unlink($work . '/home.json');
    check($provider->getDocumentCacheRevision($context) === null, 'An unavailable JSON source disables caching.');

    echo "ThemeEngine template cache checks passed.\n";
} finally {
    session_write_close();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($work, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($work);
}
