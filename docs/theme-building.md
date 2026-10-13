# Building a theme

This guide builds a small `StudioTheme` package whose header and page content are defined entirely as URPC JSON. It uses the current ThemeEngine 1.0.0 API.

An `ISectionProvider` is optional. JSON defines the block tree, and a block can name an `IURPCBlockRenderer` in its descriptor for dynamic behavior. AWT package registration and route declarations still use small PHP entry points and a `ThemePage` controller; the page structure can live entirely in URPC JSON.

Prerequisites: an AWT installation with ThemeEngine, URPC, and Dashboard installed and enabled. See [Installation and development](development.md) for requirements and [API usage](api.md) for integration methods.

## 1. Create the package structure

Use an AWT package directory matching the package namespace and name:

```text
StudioTheme/
├── manifest.json
├── main.php
├── theme.php
├── controllers/
│   └── SitePage.php
├── sources/
│   ├── header.json
│   ├── home.json
│   └── article.json
└── assets/
    └── theme.css
```

ThemeEngine uses the installed package name with spaces removed to locate its directory. `StudioTheme` avoids differences between the namespace, name, and directory. Follow the framework's usual packaging structure; this tree describes the package contents.

## 2. Declare package metadata

`manifest.json`:

```json
{
  "name": "StudioTheme",
  "version": "1.0.0",
  "minimum_awt_version": "27.0.0",
  "maximum_awt_version": null,
  "author": "Your Studio",
  "type": 2,
  "system_package": 0,
  "description": "A simple studio theme built with URPC JSON.",
  "icon": null,
  "preview_image": null,
  "license": "MIT",
  "license_url": null,
  "dependencies": [
    {"name": "ThemeEngine", "version": ">=1.0.0"},
    {"name": "URPC", "version": ">=1.0.0"},
    {"name": "Dashboard", "version": ">=1.0.0"}
  ]
}
```

`type: 2` identifies a theme. It is separate from ThemeEngine itself, which is a system package. Use dependency versions supported by AWT's package manager.

The dashboard reads metadata from the installed `awt_package` record. It does not reread the manifest each time a card renders. Install or update through the package manager to update that metadata.

Icons and cover images must resolve to browser-accessible URLs in the installed metadata. ThemeEngine accepts site-absolute paths or HTTP(S) URLs; it rejects bare relative paths, protocol-relative URLs, and values containing whitespace/control characters. For a file under the package's public `assets/` directory, use `/awt_packages/StudioTheme/assets/filename.ext`. AWT's public resource handler serves allowed asset directories and file extensions; it does not expose every package file.

If no icon or cover is supplied, the dashboard uses the theme initial and default cover. `preview_image` is a static package cover, separate from the live page preview.

## 3. Add the package entry point

`main.php`:

```php
<?php

namespace StudioTheme;

use runtime\api\RuntimeLinkerAPI;

final class StudioThemeMain extends RuntimeLinkerAPI
{
    public function environmentSetup(): void
    {
        parent::environmentSetup();
        $this->waitForPackage('ThemeEngine');
    }

    public function main(): void
    {
        // Register optional services or listeners here.
        // ThemeEngine registers page routes from theme.php.
    }
}
```

The theme definition must be available independently of this runtime's `main()` method. ThemeEngine may inspect it earlier. Do not rely on runtime-created globals or on an assumed package installation order.

## 4. Implement a page controller

`controllers/SitePage.php`:

```php
<?php

namespace StudioTheme\controllers;

use ThemeEngine\controllers\ThemePage;

final class SitePage extends ThemePage
{
    public function index(array|string $params = []): self
    {
        $this->pageData = [
            'params' => is_array($params) ? $params : [],
        ];
        return $this;
    }
}
```

The engine configures the theme's AWT Context, root directory, engine instance, and stable page key. Returning the controller lets the normal URPC pipeline render it. Preview actions must also return a `ThemePage`.

Load page data in your action and place it in `$pageData`. Providers receive it through `ThemePageContext::$data`. Validate route parameters according to your application's needs before database lookups. The preview's parameter checks are not a substitute for validation on public routes.

Constructors may run during definition validation as well as actual requests. Keep them free of rendering and persistent side effects. For constructor dependencies, configure the route's `ObjectFactory` with `setConstructorArgs([...])`.

## 5. Define page content in URPC JSON

`sources/home.json`:

```json
{
  "blocks": [
    {"descriptor": {"name": "h1"}, "content": "A place for good ideas"},
    {"descriptor": {"name": "p"}, "content": "Welcome to our studio."},
    {
      "descriptor": {"name": "a"},
      "attributes": {"href": "/article/hello-world"},
      "content": "Read an example"
    }
  ]
}
```

`sources/article.json`:

```json
{
  "blocks": [
    {"descriptor": {"name": "h1"}, "content": "Example article"},
    {"descriptor": {"name": "p"}, "content": "This page is rendered from URPC JSON."},
    {
      "descriptor": {"name": "a"},
      "attributes": {"href": "/"},
      "content": "Back home"
    }
  ]
}
```

These files supply the `content` slot directly. No PHP block construction is required. Add attributes, nested children, and any block processors supported by your URPC setup to build more complex pages.

The example article route has a slug, but this particular JSON content is static. For dynamic values, declare a renderer in the block descriptor as shown in [Dynamic JSON blocks](#dynamic-json-blocks). JSON blocks can use saved settings and page data through that renderer without replacing the section with a PHP provider. Plain JSON strings do not perform automatic interpolation.

## 6. Add a shared JSON source

`sources/header.json`:

```json
{
  "blocks": [
    {
      "descriptor": {"name": "nav"},
      "attributes": {"aria-label": "Site navigation"},
      "children": [
        {
          "descriptor": {"name": "a"},
          "attributes": {"href": "/"},
          "content": "Home"
        },
        {
          "descriptor": {"name": "a"},
          "attributes": {"href": "/article/hello-world"},
          "content": "Example article"
        }
      ]
    }
  ]
}
```

JSON follows URPC's block format. Paths passed to `SectionSource::json()` are relative to the theme package and must resolve to readable files inside it. Traversal or symlinks resolving outside the package are rejected.

The source cache stores normalized JSON and produces fresh blocks for rendering. It does not cache final HTML or page-ready mutations.

## 7. Return the theme definition

`theme.php`:

```php
<?php

use object\ObjectFactory;
use StudioTheme\controllers\SitePage;
use ThemeEngine\api\contracts\ITheme;
use ThemeEngine\api\PageDefinition;
use ThemeEngine\api\RouteDefinition;
use ThemeEngine\api\SectionSource;
use ThemeEngine\api\ThemeDefinition;

return new class implements ITheme {
    public function getDefinition(): ThemeDefinition
    {
        $factory = (new ObjectFactory())->setClassName(SitePage::class);

        return new ThemeDefinition(
            label: 'Studio Theme',
            pages: [
                'home' => new PageDefinition('home', 'Home'),
                'article' => new PageDefinition('article', 'Article', [
                    'content' => SectionSource::json('sources/article.json'),
                ]),
            ],
            routes: [
                new RouteDefinition('/', 'home', $factory),
                new RouteDefinition('/article/{slug}', 'article', $factory),
            ],
            sections: [
                'header' => SectionSource::json('sources/header.json'),
                'content' => SectionSource::json('sources/home.json'),
            ],

        );
    }
};
```

`theme.php` must return an object implementing `ITheme`; returning a `ThemeDefinition` directly is invalid.

Page keys must be 1–64 ASCII letters, digits, underscores, or hyphens. The pages array must be keyed by each page's exact key. Keep keys stable across releases because layout overrides and presets refer to them. Every route must reference a declared page.

Page routes currently support GET, with HEAD handled by AWT. Paths must start with `/` and omit query strings and fragments. `/dashboard` and `/storage` paths are reserved. Routes with the same shape, such as `/article/{id}` and `/article/{slug}`, are duplicates. Coordinate routes with other installed packages as well.

The default action is `index`. For another action, use `action: 'show'` and declare a public instance method on your theme controller. Do not register these same page routes again in `main.php`.

## 8. Override sources for individual pages

Theme-level sources apply across pages. A page may replace them:

```php
'article' => new PageDefinition('article', 'Article', [
    'content' => SectionSource::json('sources/article.json'),
    'footer' => null,
]),
```

An omitted source inherits the theme source. Explicit `null` clears it. This changes the content source, not the saved visibility setting.

Shared JSON sources retain their source namespace across pages. IDs depend on source content and structure, so source edits can change block IDs.

## 9. Style the layout and reference package assets

`assets/theme.css`:

```css
.te-page {
    --te-gap: 24px;
    --te-side-width: 240px;
    --te-max-width: 1120px;
    margin: 0;
    background: #f5f5f7;
    color: #1d1d1f;
    font: 16px/1.5 system-ui, sans-serif;
}

.te-section {
    padding: 24px;
}

.te-header a {
    margin-inline-end: 16px;
}

.studio-accent {
    height: 8px;
    margin-top: 24px;
    border-radius: 4px;
}
```

The engine supplies layout structure and its base stylesheet. Your theme styles the five logical slots: `.te-header`, `.te-side_l`, `.te-content`, `.te-side_r`, and `.te-footer`, plus `.te-page`, `.te-row`, and `.te-section`.

AWT already provides a public package-resource URL for files in allowed asset directories. For this package, the file:

```text
StudioTheme/assets/theme.css
```

is served at:

```text
/awt_packages/StudioTheme/assets/theme.css
```

Add that URL to `new ThemeDefinition(...)` in `theme.php`:

```php
stylesheets: ['/awt_packages/StudioTheme/assets/theme.css'],
scripts: [],
```

ThemeEngine adds a `<link rel="stylesheet">` element using that URL. It does not copy the CSS or register another serving route. Open the URL in your browser to check that the installed file is accessible.

For JavaScript, put the file in the same asset directory and add its URL to `scripts`. ThemeEngine emits script tags with `defer`. Asset declarations accept site-absolute or HTTP(S) URLs without whitespace. Layout CSS loads first and duplicate asset URLs are removed.

AWT's package-resource handler permits CSS and JavaScript under `assets/`, along with other allowed public resource directories and extensions. PHP files and arbitrary package paths are not public assets. `/storage/{id}/{name}` is the separate URL scheme for registered storage entries; you do not need it for this CSS file.

The default layout places header/footer above/below a row of sidebars and content. Small screens stack content before sidebars. Header/footer start enabled; sidebars start disabled. Content stays enabled. Users control optional visibility through the dashboard.

Dashboard appearance and public theme appearance are separate. The dashboard's dark mode does not automatically restyle your theme; implement public appearance behavior in the theme if needed.

## 10. Install, preview, and activate

Package the theme through the normal AWT tooling and install it from the framework root:

```bash
php awt pm install /path/to/StudioTheme-1.0.0.zip
php awt pm list
```

Enable the package using AWT's package controls. Open `/dashboard/customization` and select StudioTheme to explore its pages. For the article route, enter a slug and select Preview route. Layout controls apply to the selected theme, and the device controls let you view its pages at different sizes.
Preview uses saved values. Save each changed form before refreshing it. Once ready, select Activate theme; the next request uses the new public routes. Configuration of an inactive theme does not switch the live site.

## Dynamic JSON blocks

URPC supports dynamic processing directly in a JSON block's descriptor. Keep the section as `SectionSource::json(...)`; set `descriptor.renderer` to an autoloadable class implementing `URPC\api\contracts\IURPCBlockRenderer`.

For example, replace the home page's heading block with:

```json
{
  "descriptor": {
    "name": "h1",
    "renderer": "StudioTheme\\renderers\\SettingTextRenderer",
    "props": {"setting": "headline"}
  },
  "content": "A place for good ideas"
}
```

This object belongs in the source file's `blocks` array. JSON uses doubled backslashes in class names. `props` contains renderer configuration; it does not become HTML attributes. An omitted or empty renderer uses ordinary URPC rendering.

Create `renderers/SettingTextRenderer.php`:

```php
<?php

namespace StudioTheme\renderers;

use ThemeEngine\controllers\ThemePage;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCBlockRenderer;
use URPC\service\renderer\URPC;

final class SettingTextRenderer implements IURPCBlockRenderer
{
    public function process(IURPCBlock $block, URPC $urpc): void
    {
        if (!$urpc instanceof ThemePage) {
            return;
        }

        $props = $block->getBlockDescriptor()->getBlockProps();
        $key = $props['setting'] ?? null;
        $context = $urpc->pageContext();
        if (is_string($key) && isset($context->settings[$key])
            && is_scalar($context->settings[$key])) {
            $block->setBlockContent((string) $context->settings[$key]);
        }
    }
}
```

Declare the setting by adding this named argument to `new ThemeDefinition(...)`:

```php
settings: [
    'headline' => [
        'type' => 'text',
        'label' => 'Headline',
        'default' => 'A place for good ideas',
        'required' => true,
        'max_length' => 120,
    ],
],
```

Save a new headline in the dashboard. The JSON heading now renders that saved value through its descriptor renderer, including in previews. No `ISectionProvider` or manual `LayoutBuilder::block()` call is needed.

The renderer receives the current block and the URPC controller. For a ThemeEngine page, `$urpc->pageContext()` supplies saved settings, theme metadata, the page key, and controller data. Route parameters in the example are available at `$context->data['params']`. Generic AWT package context is available through `$urpc->getContext()`.

A renderer may mutate plain-text content, attributes, descriptor fields, or child blocks. It returns `void`; URPC produces the HTML afterward. It runs before rendering its own element and before processing its current children. Added children are processed; removed children are omitted. Changing the renderer name does not invoke another renderer on the same block during that pass.

URPC creates a fresh renderer per processed block. A class requiring constructor dependencies can receive an `ObjectFactory` through `$this->setRendererFactory(RendererClass::class, $factory)` in the page controller. The JSON still names the renderer class.

Renderers run after IDs and the page-ready event on each render. Their output is not saved into source cache, and IDs are not recalculated afterward. Preserve existing block UIDs when changing structure.

### Renderer versus section provider

| Approach | Use it for |
| --- | --- |
| URPC JSON with no renderer | Declarative block structure and ordinary text/attributes. |
| URPC JSON with `descriptor.renderer` | Dynamic behavior on individual blocks while keeping the source tree in JSON. |
| `ISectionProvider` | Generating an entire section's initial block list in PHP when that is more convenient. |

These approaches can coexist. Section providers create source blocks before layout/ID preparation. Descriptor renderers process individual blocks later, after page-ready and just before HTML generation.

## Optional: generate blocks with a PHP provider

Use this approach only if you want to generate URPC blocks in PHP from settings or page data. It is another source option, not a requirement for building a theme. JSON and provider sources can be mixed by section or page.

Create `providers/Content.php`:

```php
<?php

namespace StudioTheme\providers;

use ThemeEngine\api\contracts\ISectionProvider;
use ThemeEngine\api\ThemePageContext;
use ThemeEngine\service\LayoutBuilder;

final class Content implements ISectionProvider
{
    public function createBlocks(ThemePageContext $context): array
    {
        $blocks = [
            LayoutBuilder::block('h1', $context->settings['headline']),
        ];

        if ($context->settings['show_intro']) {
            $blocks[] = LayoutBuilder::block('p', 'Welcome to our studio.');
        }

        if ($context->pageKey === 'article') {
            $slug = $context->data['params']['slug'] ?? '';
            $blocks[] = LayoutBuilder::block('p', 'Article: ' . $slug);
        }

        $blocks[] = LayoutBuilder::block('a', 'Read an example', [
            'href' => '/article/hello-world',
        ]);

        $blocks[] = LayoutBuilder::block('div', '', [
            'class' => 'studio-accent',
            'style' => 'background-color:' . $context->settings['accent'],
            'aria-hidden' => 'true',
        ]);

        return $blocks;
    }
}
```

Return fresh `IURPCBlock` objects on each call. `LayoutBuilder::block()` accepts tag, text, attributes, children, and an optional source key. Content is plain text escaped by URPC rendering. Block renderers mutate blocks; they do not return raw HTML.

The example's inline accent value comes from the validated six-digit color setting. Avoid placing arbitrary unvalidated settings into styles, URLs, or raw HTML.

Provider instances may be reused within a request. Keep request-specific data in the context, and avoid retaining mutable block trees between calls. A disabled section never invokes its provider.

To use this provider, replace the theme-level `content` source in `theme.php`:

```php
'content' => SectionSource::provider(new \StudioTheme\providers\Content(), 'studio-content'),
```

For the article page to use the same provider, remove its JSON `content` override so it inherits the theme-level source. Give providers meaningful, stable source keys.

The provider above expects three settings. Add this named argument to `new ThemeDefinition(...)`:

```php
settings: [
                'headline' => [
                    'type' => 'text',
                    'label' => 'Headline',
                    'default' => 'A place for good ideas',
                    'required' => true,
                    'max_length' => 120,
                ],
                'accent' => [
                    'type' => 'color',
                    'label' => 'Accent color',
                    'default' => '#006edb',
                ],
                'show_intro' => [
                    'type' => 'boolean',
                    'label' => 'Show introduction',
                    'default' => true,
                ],
            ],
```

Declaring settings creates dashboard fields; it does not change JSON content by itself. This provider consumes the normalized saved values and page data. Save the fields before checking their effect in the preview.

## Extend rendering with events

Use `theme_engine.page.ready` to inspect or modify the assembled document after IDs are assigned. It runs on both public and preview renders. See the [event listener example](api.md#rendering-events).

Use the event's `context` to identify the selected theme and page. Preserve existing UIDs when modifying blocks. Replacing a document through `setDocument()` is supported, but IDs are not recalculated at this stage.

## Common integration failures

| Symptom | Check |
| --- | --- |
| Theme absent from collection | Installed metadata has `type: 2`; package name and directory match. |
| Theme visible but unavailable | Package is enabled, dependencies are ready, and `theme.php` returns `ITheme`. |
| Definition rejected | Pages are keyed correctly, routes reference them, controller extends `ThemePage`, settings defaults are valid. |
| Source rejected | JSON path resolves to a readable file inside the package. |
| Sidebar missing | Enable it in Layout and provide a source; both visibility and content are required. |
| Setting has no visual effect | Read it in a descriptor renderer, provider, controller, or listener; declaring a setting only creates its form. |
| CSS or cover missing | Installed metadata and stylesheet declarations contain accessible public URLs. |
| Preview lacks pending edits | Save the form; previews do not consume unsaved browser values. |
| Listener does not run | Register it before rendering; theme-loaded requires earlier runtime registration. |

For engine methods, validation rules, presets, and preview behavior, continue with [API usage](api.md).
