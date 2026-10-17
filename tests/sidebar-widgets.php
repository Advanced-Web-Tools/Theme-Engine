<?php

// Run with: php tests/sidebar-widgets.php
require_once __DIR__ . '/bootstrap.php';

use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\api\SidebarWidgetDefinition;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\api\ThemeRecord;
use ThemeEngine\service\contracts\IStore;
use ThemeEngine\service\customization\CustomizationViewData;
use ThemeEngine\service\Engine;
use ThemeEngine\service\JsonSourceCache;
use ThemeEngine\service\LayoutBuilder;
use ThemeEngine\service\preview\PreviewSettings;
use ThemeEngine\service\ThemeRegistry;

final class DefaultSidebarWidgetTestPage extends \ThemeEngine\controllers\ThemePage {}

function expect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function rejects(callable $call): void {
    try { $call(); } catch (InvalidArgumentException|OutOfBoundsException $expected) { return; }
    throw new RuntimeException('Invalid widget configuration was accepted');
}

$directory = sys_get_temp_dir() . '/themeengine-widgets-' . bin2hex(random_bytes(8));
mkdir($directory);
mkdir($directory . '/compiled');
define('COMPILED', $directory . '/compiled');
$store = new class implements IStore {
    public array $configs = [];
    public array $savedPresets = [];
    public function state(): array { return ['active_theme_package_id' => 1]; }
    public function saveState(array $state): void {}
    public function config(int $packageId): array { return $this->configs[$packageId] ?? ['settings' => [], 'pages' => []]; }
    public function saveConfig(int $packageId, array $config): void {
        $this->configs[$packageId] = json_decode(json_encode($config, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
    public function presets(int $packageId): array { return $this->savedPresets[$packageId] ?? []; }
    public function savePreset(int $packageId, string $name, array $payload): int {
        $id = count($this->savedPresets[$packageId] ?? []) + 1;
        $this->savedPresets[$packageId][] = compact('id', 'name', 'payload');
        return $id;
    }
    public function deletePreset(int $packageId, int $id): void {}
    public function transaction(callable $callback): mixed { return $callback(); }
};

try {
    file_put_contents($directory . '/theme.php', <<<'PHP'
<?php
return new class implements \ThemeEngine\api\contracts\ITheme {
    public function getDefinition(): \ThemeEngine\api\ThemeDefinition {
        return new \ThemeEngine\api\ThemeDefinition('Test', [
            'home' => new \ThemeEngine\api\PageDefinition('home', 'Home'),
            'article' => new \ThemeEngine\api\PageDefinition('article', 'Article'),
        ], [], ['side_l' => \ThemeEngine\api\SectionSource::provider(new class implements \ThemeEngine\api\contracts\ISectionProvider {
            public function createBlocks(\ThemeEngine\api\ThemePageContext $context): array {
                return [\ThemeEngine\service\LayoutBuilder::block('p', 'Theme sidebar')];
            }
        }, 'sidebar')]);
    }
};
PHP);
    $registry = new ThemeRegistry();
    foreach ([1, 2] as $id) $registry->register(new ThemeRecord($id, 'Theme ' . $id, $directory, '1.0.0', true));
    $engine = new Engine($registry, $store, new JsonSourceCache($directory . '/cache'), new \event\EventDispatcher());
    $engine->boot();
    $provider = new class implements ISectionProvider {
        public int $calls = 0;
        public function createBlocks(ThemePageContext $context): array {
            $this->calls++;
            return [LayoutBuilder::block('p', $context->theme->name . ':' . $context->pageKey)];
        }
    };
    $engine->widgets->register(new SidebarWidgetDefinition('Example/recent', 'Recent posts', $provider));
    $engine->widgets->register(new SidebarWidgetDefinition('Example/search', 'Search', $provider));
    rejects(fn() => $engine->widgets->register(new SidebarWidgetDefinition('Example/search', 'Duplicate', $provider)));
    rejects(fn() => new SidebarWidgetDefinition('<script>', 'Invalid', $provider));
    rejects(fn() => $engine->saveGlobalWidgets(['content' => ['Example/recent']]));
    rejects(fn() => $engine->saveGlobalWidgets(['side_l' => ['Unknown/widget']]));
    rejects(fn() => $engine->saveGlobalWidgets(['side_l' => ['Example/recent', 'Example/recent']]));
    rejects(fn() => $engine->savePageWidgets('missing', []));
    rejects(fn() => $engine->saveGlobalWidgets(['side_r' => 'invalid']));
    rejects(fn() => $engine->saveGlobalWidgets(['side_r' => [['bad']]]));

    $engine->saveGlobalWidgets(['side_l' => ['Example/recent', 'Example/search', ''], 'side_r' => ['Example/search']]);
    expect($engine->widgetsFor('home')['side_l'] === ['Example/recent', 'Example/search'], 'Widget order must persist');
    expect($engine->widgetsFor('article') === $engine->globalWidgets(), 'Pages must inherit theme widgets');
    $engine->savePageWidgets('article', ['side_l' => [], 'side_r' => ['Example/recent']]);
    expect($engine->widgetsFor('article') === ['side_l' => [], 'side_r' => ['Example/recent']], 'Explicit empty list must override theme widgets');
    expect(count($engine->widgetsFor('home')['side_l']) === 2, 'Page overrides must not affect other pages');
    $engine->savePageWidgets('article', ['side_r' => ['Example/recent']]);
    expect(count($engine->widgetsFor('article')['side_l']) === 2, 'A sidebar must inherit independently');
    $engine->savePageWidgets('article', []);
    expect(!isset($engine->pageWidgetOverrides()['article']), 'Resetting overrides must restore inheritance');

    $reloaded = new Engine($registry, $store, new JsonSourceCache($directory . '/new-cache'), new \event\EventDispatcher(), $engine->widgets);
    $reloaded->boot();
    expect($reloaded->globalWidgets() === $engine->globalWidgets(), 'New engine must load saved widget placements');

    $other = $engine->forTheme(2);
    expect(count($other->widgets->all()) === 2, 'Theme clones must share registrations');
    expect($other->globalWidgets() === ['side_l' => [], 'side_r' => []], 'Widgets must be saved per theme');
    $other->saveGlobalWidgets(['side_r' => ['Example/recent']]);
    expect(count($engine->globalWidgets()['side_l']) === 2, 'Editing another theme must preserve the active theme');

    $render = static function (string $page = 'home') use ($engine, $directory) {
        return $engine->createDocument(new ThemePageContext(new \context\Context($directory, 'Test', '1'),
            $engine->activeTheme(), $page, $engine->settings(), []));
    };
    $render();
    expect($provider->calls === 0, 'Hidden sidebars must not invoke widget providers');
    $engine->saveGlobalSections(['side_l' => '1', 'side_r' => '1']);
    $document = $render();
    $left = array_values(LayoutBuilder::findSlot($document, 'side_l')->getBlockChildren());
    expect(count($left) === 3 && $left[0]->getBlockContent() === 'Theme sidebar', 'Widgets must append to theme sidebar content');
    expect($left[1]->getBlockAttributes()['data-te-widget'] === 'Example/recent', 'Widgets must render in saved order');
    expect(array_values($left[1]->getBlockChildren())[0]->getBlockContent() === 'Theme 1:home', 'Widget must receive theme/page context');
    $left[1]->setBlockContent('Mutated');
    $fresh = array_values(LayoutBuilder::findSlot($render(), 'side_l')->getBlockChildren());
    expect($fresh[1]->getBlockContent() === '', 'Each render must return fresh widget blocks');
    $engine->savePageWidgets('article', ['side_l' => []]);
    expect(count(LayoutBuilder::findSlot($render('article'), 'side_l')->getBlockChildren()) === 1, 'Page empty override must retain theme source and remove widgets');

    $id = $engine->savePreset('With widgets');
    $engine->saveGlobalWidgets([]);
    $engine->savePageWidgets('article', []);
    $engine->applyPreset($id);
    expect(count($engine->globalWidgets()['side_l']) === 2 && $engine->widgetsFor('article')['side_l'] === [], 'Presets must restore widgets and page overrides');
    $legacy = $store->savedPresets[1][0]['payload'];
    unset($legacy['widgets'], $legacy['page_widgets']);
    $store->savePreset(1, 'Legacy', $legacy);
    $engine->applyPreset(2);
    expect(count($engine->globalWidgets()['side_l']) === 2, 'Legacy presets must retain widget placements');
    rejects(fn() => $other->applyPreset($id));

    $preview = new PreviewSettings($engine, dirname(__DIR__) . '/ThemeEngine/views');
    $preview->save(2, ['operation' => 'widgets', 'theme_id' => '1', 'widgets' => ['side_l' => ['Example/search'], 'side_r' => []]]);
    expect($other->globalWidgets() === ['side_l' => ['Example/search'], 'side_r' => []], 'Preview route must determine edited theme');
    expect($engine->globalWidgets()['side_r'] === ['Example/search'], 'Active theme must be unchanged');
    $preview->save(1, ['operation' => 'page_widgets', 'page_key' => 'article',
        'widget_mode' => ['side_l' => 'custom', 'side_r' => 'inherit'], 'widgets' => []]);
    expect($engine->widgetsFor('article')['side_l'] === [] && count($engine->widgetsFor('article')['side_r']) === 1, 'HTTP mode must distinguish empty from inherited lists');
    rejects(fn() => $preview->save(1, ['operation' => 'page_widgets', 'page_key' => 'article', 'widget_mode' => ['side_l' => 'bad']]));

    // Persisted keys whose package is unavailable are skipped and can still be removed/reordered.
    $store->configs[1]['widgets']['side_l'][] = 'Disabled/widget';
    $engine->saveGlobalWidgets($engine->globalWidgets());
    expect(count(LayoutBuilder::findSlot($render(), 'side_l')->getBlockChildren()) === 3, 'Unavailable widget must not break rendering');
    $data = (new CustomizationViewData($engine))->build('widgets', $engine->activeTheme());
    $blade = new \render\TemplateEngine\BladeOne(dirname(__DIR__) . '/ThemeEngine/views', COMPILED, \render\TemplateEngine\BladeOne::MODE_SLOW);
    $blade->setFileExtension('.awt.php');
    $blade->setPackageName('ThemeEngine');
    $html = $blade->run('preview/settings.awt.php', $data + ['previewPageRoutes' => [], 'token' => 'test-token']);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//*[@data-preview-tab="widgets"]')->length === 1, 'Preview needs a Widgets tab');
    expect($xpath->query('//form[input[@name="operation" and @value="page_widgets"]]')->length === 2, 'Both page forms must render for navigation filtering');
    expect($xpath->query('//option[@value="Disabled/widget" and @selected]')->length === 1, 'Unavailable saved widget must remain removable');
    expect($xpath->query('//fieldset[@hidden and @disabled]')->length >= 1, 'Inherited widgets must hide inactive controls');
    expect($xpath->query('//input[@name="_token"]')->length >= 3, 'Widget forms require CSRF tokens');
    $data = (new CustomizationViewData($engine))->build('widgets', $engine->activeTheme());
    expect(count($data['availableWidgets']) === 2 && isset($data['pageWidgets']['article']), 'Dashboard must expose widget data');

    $dashboardHtml = $blade->run('customization/tabs/widgets.awt.php', $data + ['token' => 'test-token']);
    expect(str_contains($dashboardHtml, '<h1>Sidebar widgets</h1>'), 'Dashboard widgets view must retain its heading');

    $engine->widgets->register(new SidebarWidgetDefinition('Example/invalid', 'Invalid', new class implements ISectionProvider {
        public function createBlocks(ThemePageContext $context): array { return [new stdClass()]; }
    }));
    $engine->saveGlobalWidgets(['side_r' => ['Example/invalid']]);
    try { $render(); throw new RuntimeException('Non-URPC widget output was accepted'); }
    catch (UnexpectedValueException $expected) {}
    // Event listeners may be installed after engine construction, as package runtimes do.
    mkdir($directory . '/navigation');
    file_put_contents($directory . '/navigation/theme.php', <<<'PHP'
<?php
return new class implements \ThemeEngine\api\contracts\ITheme {
    public function getDefinition(): \ThemeEngine\api\ThemeDefinition {
        $factory = (new \object\ObjectFactory())->setClassName(DefaultSidebarWidgetTestPage::class);
        return new \ThemeEngine\api\ThemeDefinition('Navigation theme', [
            'home' => new \ThemeEngine\api\PageDefinition('home', '<Home & test>'),
            'contact' => new \ThemeEngine\api\PageDefinition('contact', 'Contact'),
            'article' => new \ThemeEngine\api\PageDefinition('article', 'Article'),
        ], [
            new \ThemeEngine\api\RouteDefinition('/article/{id}', 'article', $factory),
            new \ThemeEngine\api\RouteDefinition('/', 'home', $factory),
            new \ThemeEngine\api\RouteDefinition('/welcome', 'home', $factory),
            new \ThemeEngine\api\RouteDefinition('/contact', 'contact', $factory),
        ]);
    }
};
PHP);
    $registry->register(new ThemeRecord(3, 'Navigation', $directory . '/navigation', '1.0.0', true));
    $eventDispatcher = new \event\EventDispatcher(); // Also used by the real URPC renderer.
    $defaultEngine = new Engine($registry, $store, new JsonSourceCache($directory . '/event-cache'), $eventDispatcher);
    $defaultEngine->boot();
    $eventDispatcher->addListener(\ThemeEngine\api\events\SidebarWidgetRegistryEvent::NAME,
        new \ThemeEngine\service\DefaultSidebarWidgets($registry));
    $eventDispatcher->addListener(\ThemeEngine\api\events\SidebarWidgetRegistryEvent::NAME,
        new \ThemeEngine\api\events\SidebarWidgetRegistrationListener(new SidebarWidgetDefinition('Example/event', 'Event widget', $provider)));
    $counter = new class implements \event\interfaces\IEventListener {
        public int $calls = 0;
        public function handle(\event\interfaces\IEvent $event): array {
            $this->calls++;
            $event->getRegistry()->all(); // Reentrant inspection must not redispatch.
            return [];
        }
    };
    $eventDispatcher->addListener(\ThemeEngine\api\events\SidebarWidgetRegistryEvent::NAME, $counter);
    $available = $defaultEngine->widgets->all();
    expect(array_keys($available) === ['ThemeEngine/pages', 'ThemeEngine/current-page', 'ThemeEngine/date', 'Example/event'], 'Defaults and third-party widgets must register through the event');
    expect($defaultEngine->globalWidgets() === $engine->globalWidgets(), 'Discovery must preserve saved placements');
    $navigation = $defaultEngine->forTheme(3);
    $navigation->widgets->all();
    expect($counter->calls === 1, 'Registration event must fire once across clones and repeated reads');
    $navigation->saveGlobalWidgets(['side_l' => ['ThemeEngine/pages', 'ThemeEngine/current-page', 'ThemeEngine/date']]);
    $navigation->saveGlobalSections(['side_l' => '1']);
    $navigationContext = new ThemePageContext(new \context\Context($directory . '/navigation', 'Navigation', '3'),
        $navigation->activeTheme(), 'home', [], []);
    $navDocument = $navigation->createDocument($navigationContext);
    $renderer = new \URPC\service\renderer\URPC();
    $renderer->setContext($navigationContext->awt);
    $renderer->setDocument($navDocument);
    $renderer->setSourceNamespace('widget-test');
    $rendered = $renderer->render();
    expect(str_contains($rendered, '&lt;Home &amp; test&gt;'), 'Built-in widgets must escape theme-provided labels');
    expect(substr_count($rendered, 'aria-current="page"') === 1, 'Navigation must mark the current page once');
    expect(str_contains($rendered, 'href="/contact"') && !str_contains($rendered, 'href="/welcome"'), 'Navigation must include concrete pages without duplicate aliases');
    expect(!str_contains($rendered, '/article/{id}'), 'Navigation must omit unresolved parameter routes');
    expect(str_contains($rendered, 'datetime="' . date('Y-m-d') . '"'), 'Date widget must output a semantic date');
    $previewHtml = (new \ThemeEngine\service\ThemePreviewNavigation())->rewrite($rendered, 3, $navigation->definition(), '/', 'https://example.test');
    expect(str_contains($previewHtml, '/dashboard/customization/themes/3/preview?route=3'), 'Built-in navigation must remain in preview for the selected theme');
    $articleContext = new ThemePageContext($navigationContext->awt, $navigation->activeTheme(), 'article', [], []);
    $current = $available['ThemeEngine/current-page']->provider->createBlocks($articleContext);
    expect($current[1]->getBlockContent() === 'Article', 'Current-page widget must use the selected theme and page');
    $current[1]->setBlockContent('Changed');
    expect($available['ThemeEngine/current-page']->provider->createBlocks($articleContext)[1]->getBlockContent() === 'Article', 'Built-ins must create fresh blocks');
    $emptyContext = new ThemePageContext($navigationContext->awt, $engine->activeTheme(), 'home', [], []);
    expect($available['ThemeEngine/pages']->provider->createBlocks($emptyContext) === [], 'No concrete page routes means no navigation content');

    $failingDispatcher = new \event\EventDispatcher();
    $retryRegistry = new \ThemeEngine\service\SidebarWidgetRegistry($failingDispatcher);
    $retryRegistry->register(new SidebarWidgetDefinition('Example/existing', 'Existing', $provider));
    $contribution = new SidebarWidgetDefinition('Example/duplicate', 'Duplicate', $provider);
    for ($i = 0; $i < 2; $i++) {
        $failingDispatcher->addListener(\ThemeEngine\api\events\SidebarWidgetRegistryEvent::NAME,
            new \ThemeEngine\api\events\SidebarWidgetRegistrationListener($contribution));
    }
    rejects(fn() => $retryRegistry->all());
    $failingDispatcher->removeListeners(\ThemeEngine\api\events\SidebarWidgetRegistryEvent::NAME);
    expect(array_keys($retryRegistry->all()) === ['Example/existing'], 'Failed event discovery must roll back partial registrations');
    echo "PASS: widget registration, scoped persistence, inheritance, URPC rendering, visibility, presets, validation, preview saves, templates, event discovery, and built-in widgets.\n";
} finally {
    $remove = static function (string $path) use (&$remove): void {
        foreach (new FilesystemIterator($path) as $file) {
            if ($file->isDir()) $remove($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($path);
    };
    $remove($directory);
}
