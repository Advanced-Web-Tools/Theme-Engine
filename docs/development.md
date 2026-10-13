# ThemeEngine developer guide

ThemeEngine 1.0.0 registers installed AWT themes, selects one active theme for the site, and assembles public pages as URPC documents. Its customization dashboard is an AWT Blade view. Theme packages are installed separately.

For a feature overview, see the [product overview](../readme.md). For integration examples, see [API usage](api.md) and [Building a theme](theme-building.md).

## Requirements and installation

Requirements: PHP 8.2 or later, MySQL/MariaDB, AWT 27 or later, URPC 1.0.0 or later, and Dashboard 1.0.0 or later. The AWT installation must provide its usual Blade and DOM extensions.

1. Install and enable URPC and Dashboard.
2. Install the ThemeEngine ZIP through the AWT package manager from the framework root:

   ```bash
   php awt pm install /path/to/ThemeEngine-1.0.0.zip
   php awt pm list
   ```

3. Open `/dashboard/customization` using an authenticated dashboard account.
4. Install and enable a theme package with `type: 2`, a valid `main.php`, and a `theme.php` returning `ITheme`.

Package enablement and theme activation are separate. Several theme packages may be enabled, but only the active theme registers public routes through ThemeEngine.

`install.php` creates the package's MySQL/MariaDB tables through AWT TableWizard and associates table ownership with the ThemeEngine package ID. Configuration is stored in these tables rather than AWT Settings. Installation must run the post-install hook; unpacking the ZIP alone is insufficient.

## Code organization

Paths in this table are relative to the `ThemeEngine/` package directory in the repository root.

| Location | Responsibility |
| --- | --- |
| `api/` | Theme declarations, page context, provider contracts, and event payloads. |
| `runtime/` | Package lifecycle, shared services, controller factories, and route registration. |
| `controllers/CustomizationController.php` | Authentication, dashboard actions, and HTTP responses. |
| `service/customization/` | Request validation, save operations, view data, URLs, and asset responses. |
| `service/Engine.php` | Theme selection, configuration, activation, and rendering coordination. |
| `service/ThemeDocumentBuilder.php` | Section sources and providers, visibility, and layout assembly. |
| `service/ThemePackageLoader.php` | Installed package metadata and dependency status. |
| `service/ThemePreview*.php`, `service/preview/` | Preview rendering, navigation rewriting, and standalone window data. |
| `service/DatabaseStore.php`, `service/JsonSourceCache.php` | Persistence and source caching. |
| `views/customization/tabs/`, `views/customization/partials/` | Dashboard tabs and reusable template pieces. |
| `views/preview/window.php` | Plain PHP standalone preview shell. |
| `assets/themes.js`, `assets/themes.css` | Dashboard behavior and appearance; JavaScript initialization is split by feature. |

Customization templates use literal relative include paths with `.awt.php`, as required by AWT Blade. Includes inside loops explicitly receive local variables; other view data comes from the controller bundle.

The organization refactor preserves existing public/protected signatures, route names and paths, stored configuration and cache formats, and event timing. It requires no database migration.

## Runtime architecture

`main.php` implements RuntimeLinkerAPI, waits for URPC, and links these runtimes in order:

| Runtime | Responsibility |
| --- | --- |
| `ThemeLoaderRuntime` | Loads installed type-2 packages into the registry, loads the active `theme.php`, publishes the shared engine, and registers `PageReadyBridge`. |
| `ControllerRuntime` | Prepares the dashboard factory and the active theme's page factories. |
| `DashboardRouterRuntime` | Registers customization GET routes, POST actions, and asset routes. |
| `PublicRouterRuntime` | Registers the active theme's declared public routes and dispatches `theme_engine.theme.loaded`. |

A theme should declare its public page routes through `theme.php` rather than registering them again in `main.php`. The theme's package runtime may register services, block processors, event listeners, and other service routes.

The engine can read a theme definition before that theme's `main()` runs. Keep the definition independently available. If it needs another runtime, declare that dependency explicitly instead of relying on package installation order.

Other runtimes can access the shared engine:

```php
$this->waitForPackage('ThemeEngine'); // In environmentSetup().
$engine = $this->getShared('ThemeEngine', 'engine', \ThemeEngine\service\Engine::class);
```

## Public pages and section providers

Theme controllers extend `ThemeEngine\controllers\ThemePage`, which extends URPC. The engine injects a document provider, source namespace, and the theme package's AWT Context. Controllers prepare `$pageData`; section providers receive it through `ThemePageContext::$data`.

Sections accept JSON sources through `SectionSource::json()` or PHP objects implementing `ISectionProvider`. JSON uses the existing URPC format. JSON blocks can declare `descriptor.renderer` classes implementing `IURPCBlockRenderer` for dynamic per-block processing. PHP section providers are optional and must return fresh blocks on every call. Configure controllers with constructor dependencies through `ObjectFactory::setConstructorArgs()`.

A `PageDefinition` has a stable key, such as `article`, shared by URLs such as `/article/{id}`. Themes declare GET page routes; AWT supports HEAD through GET. Other service routes can be registered in the theme's package runtime.

Ordinary AWT/Blade views use `.awt.php`. Public `ThemePage` rendering uses JSON/PHP providers; the dashboard uses Blade. ThemeEngine does not convert HTML or Blade templates into URPC blocks.

Public `ThemePage` output includes the HTML5 doctype. The document title uses the page and theme names.

## Five-section layout

The logical slots are `header`, `side_l`, `content`, `side_r`, and `footer`. The header and footer sit above and below the main row; sidebars sit beside the content. On small screens, content precedes the sidebars. Content is always enabled. Initial defaults enable the header and footer and disable both sidebars.

The engine builds a nested URPC tree with HTML, head, and body wrappers. The assembled `IURPCDocument::getSections()` contains one technical section, `layout`. Logical slots are live blocks marked with `data-te-section`, allowing nested columns without modifying URPC core.

Use `PageReadyEvent::getSection('content')` or `LayoutBuilder::findSlot($document, 'content')` to access a slot. `$document->getBlocks('content')` does not address the logical slot in the assembled document.

Default visibility can be overridden per page with `inherit`, `on`, or `off`. Disabled sections do not invoke their providers. A `PageDefinition` can replace a shared section source or set it to `null` to remove its content.

Themes style the layout through these hooks:

- Variables: `--te-gap`, `--te-side-width`, `--te-max-width`.
- Classes: `.te-page`, `.te-row`, `.te-section`, `.te-header`, `.te-footer`, `.te-content`, `.te-side_l`, `.te-side_r`.

Stylesheet and script URLs from `ThemeDefinition` are deduplicated, with layout CSS loaded first from the public `/awt_packages/ThemeEngine/assets/layout.css` asset. Public rendering does not depend on the authenticated dashboard CSS endpoint. For package assets, supply public URLs such as `/awt_packages/StudioTheme/assets/theme.css`. AWT serves allowed resource directories and extensions; ThemeEngine adds the declared stylesheet and script URLs to the document.

## Events

| Event | Timing | Payload / API |
| --- | --- | --- |
| `theme_engine.theme.loaded` | Once per request engine instance, after active-theme route registration. Omitted when no valid theme is active. | `ThemeLoadedEvent`: `theme`, `definition`. |
| `theme_engine.page.ready` | On every `ThemePage` URPC render, after layout assembly and URPC ID assignment, before block processors and HTML generation. Includes preview renders. | `PageReadyEvent`: `getDocument()`, `setDocument()`, `getSection()`, `context`, `controller`. |

`PageReadyBridge` listens to `urpc.before_render` and filters for `ThemePage` controllers. Listeners may mutate live blocks or replace the document; replacements are forwarded to URPC. Preserve the original block UID when overriding a block. IDs are not recalculated after this event.

URPC IDs depend on source content, structure, and source keys. Editing source content can change IDs. Shared JSON sections retain their original namespace across pages instead of being assigned the page key. This provides a future integration point for Quill; Quill is not included or required.

Register `theme_engine.theme.loaded` listeners before `PublicRouterRuntime` runs. Page-ready listeners may be registered after runtime setup, provided they are registered before the HTTP route is handled.

## Settings, presets, and activation

| Table | Stored data |
| --- | --- |
| `theme_engine_state` | One row, `id=1`: active theme ID and legacy site-wide section visibility. |
| `theme_engine_settings` | One row per `package_id`: theme settings, default section visibility, and page overrides. |
| `theme_engine_presets` | Package-specific presets, each with a name and a complete configuration snapshot. |

Generated settings forms support text, textarea, boolean, number with min/max, select, color, and HTTP(S) URL fields. Fields have a label and default, with optional description, required, and max-length constraints. The server validates values and rejects unknown keys. Each theme retains its settings when activation changes.

Default layout visibility is stored in each theme's configuration under `sections`, alongside its settings and page overrides. Legacy site-wide defaults remain the fallback for themes without saved layout preferences.

A preset captures the selected theme's settings, default section visibility, and page overrides. Applying it is transactional and does not activate the theme. Presets cannot be applied or deleted through another theme's ID. After schema changes, unknown fields are omitted and new or invalid values use defaults. Deleting a preset removes only that snapshot. Multiple presets may share a name.

Activation validates the theme contract, controllers, declared JSON sources, reserved dashboard/storage paths, and duplicate routes within the theme. A new selection takes effect on the next request. AWT RouterManager does not report every route conflict with other packages, so coordinate public paths with those packages. An invalid active theme displays an error while leaving the customization dashboard available.

## Source cache

The source cache uses the private `COMPILED/ThemeEngine` directory, the source JSON content hash, and its logical source key. It stores normalized JSON blocks and hydrates fresh objects without PHP `unserialize()`. Source edits invalidate the corresponding entry; corrupt entries are rebuilt. An unwritable cache directory does not prevent rendering.

The cache excludes finished HTML, event mutations, PHP provider data, and user-specific documents. Providers and `theme_engine.page.ready` run on every render. The dashboard includes a Clear cache action.

## Customization dashboard

`/dashboard/customization` offers Themes, Layout, Theme settings, Presets, and Help tabs. `CustomizationController` enforces dashboard authentication and permission level 1. Save actions are POST-only and use AWT CSRF protection. Successful saves use POST-redirect-GET to avoid repeated submissions on refresh.

Theme cards open `/dashboard/customization/themes/{package_id}`. The active theme appears first and spans the collection width. Detail pages keep About theme, Layout, Theme settings, and Presets scoped to the selected package. Enabled inactive themes can be configured before activation. Forms, redirects, and validation errors retain that package's context. Disabled or invalid themes explain why editing is unavailable.

### Help

The Help tab uses native expandable questions for getting started, customization, previews, saving changes, and troubleshooting. Links to settings, layout, presets, and theme details retain the selected package. On theme pages, a status panel shows package enablement, activation, dependency status, and customization errors. Help remains available for disabled or invalid themes. The existing Clear cache action remains available at the bottom of the page.

### Package metadata and images

Cards and detail headers use `icon` and `preview_image` URLs from installed package metadata. Missing icons use the theme name's initial. Missing preview images use `assets/default_preview.png`; failed image URLs also fall back to that asset. If the fallback fails, the image is hidden and the solid background remains.

The detail page displays the description, version, author, license link, AWT compatibility range, installation date, package and activation status, dependencies, and declared routes. Dependency badges use installed versions and enablement with AWT version rules, distinguishing compatible, missing, disabled, incompatible, and uncheckable requirements. Installed dependency versions and source links are shown where available.

### Live previews

The embedded preview renders the selected enabled theme with its saved settings and layout overrides. It does not change persistent activation or public route registration. Parameterized routes expose inputs for placeholders. Disabled packages and invalid definitions show an unavailable message.

The iframe uses `sandbox="allow-scripts allow-same-origin"`, preserving the site origin and dashboard session cookies during preview navigation. Links matching the selected theme's routes are rewritten into preview URLs, preserving parameters, query strings, and fragments. External links, downloads, and non-theme routes keep their original destinations.

Device controls resize the frame without reloading its route:

| Device | Preview dimensions |
| --- | --- |
| Desktop | Full available width and height. |
| Tablet | 768 × 1024 pixels. |
| Mobile | 390 × 844 pixels. |

Oversized frames can be scrolled horizontally. The separate preview window starts at the selected device size and has connected, icon-only 48-pixel size buttons stacked at the bottom right. Only the stack's outer top and bottom corners are rounded. Hover titles identify each device; the stack has no visible container or dimensions label. Controls sit outside the theme frame, so preview navigation retains the controls and selected size.

The standalone window also has an icon-only Font Awesome settings button at the bottom left. It opens an animated floating popup with Theme settings, Layout, and Presets tabs, using the same forms as the dashboard. Layout includes theme defaults and individual page overrides. Changes take effect after Save or Apply and reload the current route inside the frame. Saving one form preserves unsaved changes in other forms; closing the popup also retains them. Applying a preset or restoring defaults asks before replacing unsaved edits. The popup supports keyboard tab navigation, Escape to close, light/dark appearance, and reduced motion.

The popup uses authenticated `/dashboard/customization/themes/{package_id}/preview-settings` and POST `/dashboard/customization/themes/{package_id}/preview-settings/save` endpoints. Saves retain the existing CSRF protection and schema validation, with the route determining the theme being edited.

On screens up to 1024 pixels wide or with a coarse pointer, switching is disabled and the preview fills the available screen. The embedded dashboard preview's dimensions label updates as its frame resizes.

### Appearance and unsaved changes

Preview controls follow the dashboard's saved light/dark preference, falling back to the system preference. Separate windows apply it before styles load and synchronize changes from other dashboard windows.

Customization tabs, devices, navigation, and form actions use AWT's bundled Font Awesome Free 6.5 assets through fixed CSS and allowlisted WOFF2 routes. Decorative icons retain visible labels. Headings, metadata, status badges, and feedback use plain text.

Settings, default layout, page overrides, and preset-name forms track unsaved changes. The message includes a Save button for each changed form, labeled by action or page name. It submits the original form with native validation, CSRF, and theme context. Restoring original values clears the indicator. Leaving triggers the browser reminder; submitting one changed form still protects pending changes in other forms.

## Development builds

Build commands run from the repository root, one directory above `docs/`. The build tool packages the project using its manifest and can install it on a development server.

Requirements: PHP, the ZIP extension for packaging, and cURL for remote installation.

```bash
# Linux / macOS
chmod +x build.sh
./build.sh

# Windows
build.bat
```

Configuration lives in `build/dev_env.json`:

| Key | Purpose |
| --- | --- |
| `release_folder` | Directory for packaged ZIP files. |
| `build_save_format` | ZIP filename template using `<name>`, `<version>`, `<description>`, `<author>`, or `<license>` from the manifest. |
| `address` | Development server base URL. |
| `remote_install_path` | Remote package installation endpoint. |
| `devSecret` | Installation secret matching the development server configuration. |

Use `--install` to install automatically after building. Without it, the tool asks before remote installation. Use `--fast` to package files changed since the previous build. The cache at `build/.file_cache.json` stores file hashes; deleting it forces a full rebuild.

```bash
./build.sh --install
./build.sh --fast --install

# Equivalent Windows commands
build.bat --install
build.bat --fast --install
```

Remote installation is for development environments. In the server's `awt_config.php`, enable `DEBUG` and `REMOTE_INSTALL_FOR_DEVS`, then match `dev_secret` to the local `devSecret`. Keep remote installation disabled in production.

## Response compatibility

The CSS response uses `Response::file()` instead of `Response::data()`, which accepts only arrays.
