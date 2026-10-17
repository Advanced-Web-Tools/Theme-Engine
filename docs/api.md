# API usage

ThemeEngine exposes PHP contracts and services inside AWT. The customization URLs are authenticated dashboard endpoints, not a JSON REST API.

See [Building a theme](theme-building.md) for a complete package example and [Development](development.md) for runtime order and installation.

## Obtain the shared engine

From an AWT runtime, declare the dependency in `environmentSetup()`, then retrieve the initialized engine when your runtime runs:

```php
use ThemeEngine\service\Engine;

// Inside environmentSetup(), after the parent's setup:
$this->waitForPackage('ThemeEngine');

// Inside main():
$engine = $this->getShared('ThemeEngine', 'engine', Engine::class)
    ?? throw new \LogicException('ThemeEngine is not available.');
```

The package publishes the engine under package name `ThemeEngine` and shared key `engine`. Use that instance instead of constructing a second production engine with separate storage or event wiring.

## Inspect and select themes

```php
$installedThemes = $engine->registry->all(); // Records keyed by package ID.
$record = $engine->registry->get($packageId);
$definition = $engine->registry->load($packageId);

if ($engine->hasActiveTheme()) {
    $active = $engine->activeTheme();
    $activeDefinition = $engine->definition();
}

// Select a package for editing without activating it on the site.
$editing = $engine->forTheme($packageId);
$values = $editing->settings();
```

`forTheme()` clones the engine and selects the requested theme for that instance. Registry, storage, cache, and dispatcher remain shared. Reads and writes on the clone target the selected package. `forPreview()` performs the same selection for preview rendering. On a selected clone, `activeTheme()` means that instance's selected theme; it does not prove that the site has activated it.

`ThemeRecord` contains package ID, name, root directory, version, enablement, description, icon, preview image, dependencies, author, license, license URL, AWT version bounds, and installation date. Metadata is readonly. `ThemeDefinition` contains the theme's label, pages, routes, sources, settings schema, stylesheet URLs, and script URLs.

Unknown package IDs raise `OutOfBoundsException`. Loading a disabled package raises `LogicException`; malformed declarations and invalid files also raise exceptions. If the persisted active theme fails during boot, `loadError` contains the failure and `hasActiveTheme()` remains false.

## Activate a theme

```php
$engine->activate($packageId);
```

Activation validates the theme and warms its declared JSON sources before persisting the selection. The current engine instance and already registered public routes remain as they were; the new selection takes effect on the next request.

Do not use activation to render a preview. Use `forTheme()` or `ThemePreview` instead. In HTTP integrations, enforce your application's authentication, permission, and CSRF rules before calling methods that write configuration.

## Sidebar widgets

Sidebar widgets are separate from Dashboard widgets. Enabled package runtimes contribute them through `SidebarWidgetRegistryEvent` (`theme_engine.widgets.register`). ThemeEngine fires this event once on first registry lookup or rendering; selected theme clones share the same registry. Registrations are rebuilt each request; only placements are persisted.

```php
use ThemeEngine\api\SidebarWidgetDefinition;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\service\LayoutBuilder;
use ThemeEngine\api\events\SidebarWidgetRegistryEvent;
use ThemeEngine\api\events\SidebarWidgetRegistrationListener;

// In your package runtime main(), after waiting for ThemeEngine:
$widget = new SidebarWidgetDefinition(
    key: 'MyPackage/welcome',
    label: 'Welcome',
    provider: new class implements ISectionProvider {
        public function createBlocks(ThemePageContext $context): array
        {
            return [LayoutBuilder::block('p', 'Welcome to ' . $context->theme->name)];
        }
    },
);
$this->eventDispatcher->addListener(
    SidebarWidgetRegistryEvent::NAME,
    new SidebarWidgetRegistrationListener($widget),
);
```

A listener that contributes several widgets can implement `IEventListener`, check for `SidebarWidgetRegistryEvent`, and call `$event->getRegistry()->register(...)`. Install listeners during runtime startup; defer registry reads and placement saves until all runtimes have run. Direct `$engine->widgets->register(...)` registration remains supported.

ThemeEngine supplies these defaults through its own `DefaultSidebarWidgets` listener:

| Key | Label | Content |
| --- | --- | --- |
| `ThemeEngine/pages` | Pages | Links to concrete pages of the selected theme, marking the current page. Parameterized routes and duplicate page aliases are omitted. |
| `ThemeEngine/current-page` | Current page | The selected page's label. |
| `ThemeEngine/date` | Today | The current date in the site's PHP timezone. |

Choose them in **Widgets** and save their sidebar placements. Registration does not automatically place widgets or change existing configurations. Built-ins produce fresh URPC blocks for the selected theme, including inactive-theme previews. Page links follow the normal preview navigation rewriting.

After runtime startup, placement APIs can be used from an authenticated customization action:

```php
$editing = $engine->forTheme($packageId);
$editing->saveGlobalWidgets([
    'side_l' => ['MyPackage/welcome'],
    'side_r' => [],
]);
$editing->savePageWidgets('article', [
    'side_l' => [], // No widgets on this page's left sidebar.
    // Missing side_r inherits the theme's right sidebar widgets.
]);
$editing->savePageWidgets('article', []); // Restore inheritance for both sidebars.
$placements = $editing->widgetsFor('article');
$registered = $engine->widgets->all();
```

Providers implement `ISectionProvider` and must return fresh `IURPCBlock` objects on every call. They receive the selected theme, saved settings, page key, and page data. Duplicate registration keys, unknown widget keys, repeated widgets within a sidebar, unknown pages, and placements outside `side_l`/`side_r` are rejected. Up to 50 widgets can be placed in each sidebar, in saved order. The same widget can appear once in each sidebar.

Enable the desired sidebar in Layout. Widgets append after any existing theme/page sidebar source. Disabled sidebars never invoke widget providers. If a package is disabled or removed, its saved widget keys remain editable but are skipped during rendering. Re-registering the key restores its placements.

Theme and page placements are stored in `theme_engine_settings.payload`, scoped by `package_id`:

```json
{
  "widgets": {"side_l": ["MyPackage/welcome"], "side_r": []},
  "page_widgets": {"article": {"side_l": []}}
}
```

Missing or `null` page sidebar values inherit; an explicit empty list removes that sidebar's widgets. Presets include both placement maps. Older presets without widgets retain the existing placements. No schema migration is needed.

## Settings

```php
$editing = $engine->forTheme($packageId);
$current = $editing->settings();

// Example for the optional provider schema in the theme-building guide:
$editing->saveSettings([
    'headline' => 'Welcome to our studio',
    'accent' => '#006edb',
    'show_intro' => '1',
]);

$editing->resetSettings(); // Clears stored values; schema defaults apply.
```

`saveSettings()` validates a complete submission against the selected theme's schema. Supply all non-boolean fields. Missing booleans normalize to false. Unknown keys are rejected rather than silently ignored.

`settings()` returns normalized values: absent or invalid stored values use schema defaults, and obsolete keys are omitted. Resetting settings does not reset layout visibility or page overrides.

| Type | Accepted values and constraints |
| --- | --- |
| `text`, `textarea` | Strings; optional `required` and `max_length`. Default maximum is 4096 bytes. |
| `boolean` | `true`, `false`, `0`, `1`, `'0'`, or `'1'`; normalizes to bool. |
| `number` | Finite numeric strings, ints, or floats; optional `min` and `max`; normalizes to float. |
| `select` | A string matching a key in the field's `options`. |
| `color` | A six-digit `#RRGGBB` string. |
| `url` | HTTP(S) URL, or an empty string when allowed. |

For string fields, `required` rejects blank values. Length limits use bytes rather than Unicode character counts.

## Layout visibility

The optional sections are `header`, `footer`, `side_l`, and `side_r`. `content` is always enabled.

```php
$editing->saveGlobalSections([
    'header' => true,
    'footer' => true,
    'side_l' => false,
    'side_r' => false,
]);

$editing->savePageSections('home', [
    'header' => 'inherit',
    'footer' => 'on',
    'side_l' => 'off',
    'side_r' => 'inherit',
]);

$defaults = $editing->globalSections();
$overrides = $editing->pageOverrides();
$effective = $editing->sectionsFor('home'); // Includes content => true.
```

Despite the method name, `saveGlobalSections()` saves defaults for the instance's selected theme. When the engine has no selected theme, it saves the legacy site-wide fallback instead.

Default visibility accepts booleans and `0`/`1` integer or string values. Omitted optional sections default to false. Page overrides accept only `inherit`, `on`, and `off`; omitted sections inherit. Unknown section names and page keys are rejected.

Visibility and source selection are separate: a visible section can have no content source, and an invisible section never calls its provider.

## Presets

```php
$presetId = $editing->savePreset('Launch layout');
$presets = $editing->presets();
$editing->applyPreset($presetId);
$editing->deletePreset($presetId);
```

A preset captures the selected theme's currently saved settings, default visibility, and page overrides. It does not capture unsaved form values or activate the theme. Names are trimmed and must contain 1–120 bytes; duplicate names are allowed.

Applying a preset is transactional and revalidates its contents against the current schema and known pages. Invalid or missing setting values fall back to defaults. Another theme's preset cannot be applied. Deletion is scoped to the selected package and does not delete another package's preset.

## Page context and document construction

`ThemePageContext` is passed to providers and page-ready listeners:

| Property | Meaning |
| --- | --- |
| `awt` | AWT Context for the theme package. |
| `theme` | Selected theme's `ThemeRecord`. |
| `pageKey` | Stable key declared in `PageDefinition`. |
| `settings` | Normalized saved settings for this theme. |
| `data` | Controller's `$pageData`, including route parameters if supplied. |

Controllers extending `ThemePage` receive the configured engine and page key. Normally, let the controller's URPC rendering pipeline obtain its document. `Engine::createDocument(ThemePageContext $context): IURPCDocument` assembles a document for the selected theme and declared page; it alone does not execute the URPC render pipeline or dispatch page-ready.

The assembled document has a technical `layout` section. Logical slots are nested blocks. Use `LayoutBuilder::findSlot($document, 'content')` or the event's `getSection()` method instead of requesting a top-level `content` section.

## Dynamic block renderers in JSON

A section loaded with `SectionSource::json()` can name a dynamic renderer in any block's descriptor:

```json
{
  "descriptor": {
    "name": "h1",
    "renderer": "StudioTheme\\renderers\\SettingTextRenderer",
    "props": {"setting": "headline"}
  },
  "content": "Fallback heading"
}
```

The class implements `URPC\api\contracts\IURPCBlockRenderer` with `process(IURPCBlock $block, URPC $urpc): void`. URPC instantiates it and calls `process()` for that block after page-ready, before emitting its element and processing its children. It mutates content, attributes, or structure; it does not return HTML. Text content remains escaped.

When `$urpc` is a `ThemePage`, `$urpc->pageContext()` provides the selected theme, saved settings, page key, and controller data. `$urpc->getContext()` exposes the AWT Context. Renderer configuration is read from `$block->getBlockDescriptor()->getBlockProps()`.

For constructor dependencies, configure a factory on the page controller:

```php
$factory = (new \object\ObjectFactory())->setConstructorArgs([$repository]);
$this->setRendererFactory(\StudioTheme\renderers\ProductRenderer::class, $factory);
```

This snippet belongs in a page action or controller setup with an available repository and an actual renderer class. Each processed block gets a fresh renderer created from a cloned factory. JSON continues to name the class.

JSON renderers do not require a PHP section provider. See [Dynamic JSON blocks](theme-building.md#dynamic-json-blocks) for a complete implementation and settings example. Section providers create initial source blocks; descriptor renderers operate on individual blocks during HTML rendering.

## Rendering events

### Page ready

`theme_engine.page.ready` fires on every public or preview `ThemePage` URPC render, after layout assembly and ID assignment and before block processors and HTML output.

Register an `IEventListener`, not a closure:

```php
use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use ThemeEngine\api\events\PageReadyEvent;

$engine->dispatcher->addListener(PageReadyEvent::NAME, new class implements IEventListener {
    public function handle(IEvent $event): array
    {
        if (!$event instanceof PageReadyEvent || $event->context->pageKey !== 'home') {
            return [];
        }

        $content = $event->getSection('content');
        if ($content !== null) {
            $attributes = $content->getBlockAttributes();
            $attributes['data-page-key'] = $event->context->pageKey;
            $content->setBlockAttributes($attributes);
        }

        return [];
    }
});
```

Register before the HTTP route renders. `getSection()` returns the live wrapper or null for a disabled slot, and rejects unknown slot names. Changes to its attributes or children affect output.

`getDocument()` exposes the document; `setDocument($replacement)` replaces it and forwards the replacement back to URPC. `context` and `controller` are readonly references. `bundle()` returns document, context, and controller.

Preserve existing block UIDs when changing blocks. IDs are not recalculated after this event. New or replacement blocks needing assigned IDs must be prepared accordingly. Listeners also run in previews, so base theme decisions on `$event->context->theme`, not solely on the site's persisted activation.

### Theme loaded

`ThemeLoadedEvent::NAME` is `theme_engine.theme.loaded`. It supplies readonly `theme` and `definition` properties and a corresponding `bundle()`.

The shared engine dispatches it once per request engine instance after active-theme public route registration. It is omitted when no valid theme is active. Arrange listener registration before ThemeEngine's `PublicRouterRuntime`; registering after the package has completed misses this event. `forTheme()` does not announce activation or automatically dispatch theme-loaded.

## Programmatic previews

```php
use ThemeEngine\service\ThemePreview;

$preview = new ThemePreview($engine);
$routes = $preview->routes($packageId);

// Each entry contains index, path, method, label, and parameters.
foreach ($routes as $route) {
    if ($route['parameters'] === []) {
        $html = $preview->render($packageId, $route['index']);
        break;
    }
}
```

For a declared `/article/{slug}` route, pass `['slug' => 'hello-world']` as the third render argument, using that route's actual index. Each required parameter must be a nonblank string of at most 512 bytes. Route indices follow the declaration array; retrieve them instead of assuming they stay constant after route reordering.

Rendering configures and calls the declared controller action, requires it to return a `ThemePage`, and runs URPC rendering. Saved theme configuration and page-ready listeners apply. No activation write or public route registration occurs. Links matching the selected theme's routes are rewritten into preview URLs.

These are real controller executions: keep GET actions and rendering listeners free of unintended persistent side effects.

Authenticated browser preview URLs use:

```text
/dashboard/customization/themes/{package_id}/preview?route=0
/dashboard/customization/themes/{package_id}/preview?route=1&params[slug]=hello-world
/dashboard/customization/themes/{package_id}/preview?route=0&window=1&device=tablet
```

Use URL encoding when constructing parameter values. Valid devices are `desktop`, `tablet`, and `mobile`. These endpoints require dashboard access and return HTML, with `no-store` caching and same-origin frame restrictions. Invalid preview requests return 422; unknown themes or routes return 404.

## Cache and lower-level services

`$engine->cache->clear()` clears source cache, leaving settings and presets intact. Cache entries hold normalized JSON sources, not finished HTML or provider results.

`ThemeRegistry`, `IStore`, and `JsonSourceCache` are available for custom integration. Prefer engine methods for production configuration changes because they validate schema, ownership, and page keys. Do not write the storage tables directly to bypass those checks.
