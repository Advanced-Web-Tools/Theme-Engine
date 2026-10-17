<h1>Help with your themes</h1>
<p class="lead mb-4">Choose a topic below for guidance on previewing, customizing, and saving your themes.</p>

<div class="card-stack content-panel">
    @if($selectedTheme)
        <section class="card" aria-labelledby="theme-help-status-heading">
            <h2 id="theme-help-status-heading" class="h4">Help for {{ $selectedTheme->name }}</h2>
            <p>
                Package {{ $selectedTheme->enabled ? 'enabled' : 'disabled' }}.
                {{ $engine->hasActiveTheme() && $engine->activeTheme()->packageId === $selectedTheme->packageId ? 'This is your active theme.' : 'This theme is not active on your site.' }}
            </p>
            @if($themeError)
                <div class="alert alert-danger" role="alert">Customization and preview are unavailable: {{ $themeError }}</div>
                <p>Check that the package is enabled and its theme definition is valid. Review its dependencies below.</p>
            @else
                <p>You can preview and configure this theme without activating it. Changes apply to this package only.</p>
            @endif
            @if(count($selectedTheme->dependencies) > 0)
                <h3 class="h5">Dependency status</h3>
                <ul class="theme-dependencies">
                    @foreach($selectedTheme->dependencies as $dependency)
                        <li>
                            <span>{{ $dependency['name'] }}</span> <code>{{ $dependency['version'] }}</code>
                            <span class="badge theme-dependency-status theme-dependency-{{ $dependency['status'] ?? 'unknown' }}">{{ $dependency['status_label'] ?? 'Not checked' }}</span>
                            @if(!empty($dependency['installed_version']))
                                <span class="text-small text-muted">Installed {{ $dependency['installed_version'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            <a href="{{ $customizationUrl }}?tab=themes">View theme details</a>
        </section>
    @endif

    <section class="card theme-help-section" aria-labelledby="help-start-heading">
        <h2 id="help-start-heading" class="h4">Getting started</h2>
        <details open>
            <summary>How do I choose and activate a theme?</summary>
            <div class="theme-help-answer">
                <p>Open Themes and select a card to review its details and preview its pages. Choose Activate theme when you are ready. The selected theme serves your site from the next request.</p>
                <p>The active theme appears first in the collection. Theme packages must be installed and enabled before they can be activated.</p>
                <a href="/dashboard/customization?tab=themes">Browse all themes</a>
            </div>
        </details>
        <details>
            <summary>Can I prepare a theme before activating it?</summary>
            <div class="theme-help-answer">
                <p>Yes. Open an enabled theme's detail page and use its Layout, Theme settings, and Presets tabs. Each theme keeps its own saved configuration, and editing an inactive theme leaves the active theme unchanged.</p>
                <a href="{{ $customizationUrl }}?tab=settings">Open theme settings</a>
            </div>
        </details>
    </section>

    <section class="card theme-help-section" aria-labelledby="help-customize-heading">
        <h2 id="help-customize-heading" class="h4">Customize your theme</h2>
        <details>
            <summary>What can I change in Theme settings?</summary>
            <div class="theme-help-answer">
                <p>The theme supplies its available fields, such as text, colors, and other options. Change their values and select Save settings. If no fields appear, the theme does not provide additional settings.</p>
                <a href="{{ $customizationUrl }}?tab=settings">Open theme settings</a>
            </div>
        </details>
        <details>
            <summary>How do layout defaults and page overrides work?</summary>
            <div class="theme-help-answer">
                <p>Default layout controls the header, footer, and left and right sidebars for the theme. Content is always included. Save the defaults with Save layout.</p>
                <p>Each page can use the default, show a section, or hide it. Save page layout applies that page's choices. Hiding a section removes it from the rendered layout.</p>
                <a href="{{ $customizationUrl }}?tab=layout">Open layout</a>
            </div>
        </details>
        <details>
            <summary>How do I add sidebar widgets?</summary>
            <div class="theme-help-answer">
                <p>Open Widgets, choose widgets for the left or right sidebar, and save. Use the arrows to change their order. Enable the sidebar in Layout.</p>
                <p>Each page can use the theme's widgets or choose its own. An empty page selection removes widgets from that sidebar. Widgets become available when an enabled package registers them.</p>
                <a href="{{ $customizationUrl }}?tab=widgets">Open widgets</a>
            </div>
        </details>
        <details>
            <summary>What does a preset save?</summary>
            <div class="theme-help-answer">
                <p>A preset captures the theme's saved settings, layout, page overrides, and sidebar widgets. Save your configuration forms first, then name and save a preset.</p>
                <p>Applying a preset restores that theme's configuration without activating it. Deleting a preset removes the snapshot. Presets belong to the theme that created them.</p>
                <a href="{{ $customizationUrl }}?tab=presets">Manage presets</a>
            </div>
        </details>
    </section>

    <section class="card theme-help-section" aria-labelledby="help-preview-heading">
        <h2 id="help-preview-heading" class="h4">Using previews</h2>
        <details>
            <summary>How do I preview a page?</summary>
            <div class="theme-help-answer">
                <p>Open a theme's About theme tab and find Available pages. Enter any required details, such as an article ID, then select View page. The page uses that theme's saved settings and layout.</p>
                <p>Links to the theme's other routes stay in preview mode. External links and links outside the theme keep their normal destinations.</p>
                <a href="{{ $customizationUrl }}?tab=themes">{{ $selectedTheme ? 'View available pages' : 'Choose a theme to preview' }}</a>
            </div>
        </details>
        <details>
            <summary>How do preview controls work?</summary>
            <div class="theme-help-answer">
                <p>Desktop fills the available space. Tablet uses 768 × 1024 pixels, and Mobile uses 390 × 844 pixels. Switching sizes keeps the current preview page loaded.</p>
                <p>Device controls sit at the bottom right; settings open from the bottom left. Global settings and layout defaults apply across the theme. Page layout controls show only the page being previewed and follow navigation. Presets replace the configuration for the whole theme. Saving changes affects the live site when this theme is active. On phone and tablet screens, the preview follows the available screen.</p>
            </div>
        </details>
    </section>

    <section class="card theme-help-section" aria-labelledby="help-save-heading">
        <h2 id="help-save-heading" class="h4">Saving changes</h2>
        <details>
            <summary>What does the unsaved-changes message mean?</summary>
            <div class="theme-help-answer">
                <p>The message appears when an editable form differs from its saved values. Use its labeled Save button or the Save button in the form. Both validate and submit the same form.</p>
                <p>Returning fields to their original values clears the message. Previews show saved values, so save your changes before checking them there.</p>
            </div>
        </details>
        <details>
            <summary>What if I changed several forms?</summary>
            <div class="theme-help-answer">
                <p>Each form saves separately. The message provides a button for every changed form. Saving one form leaves the browser reminder active for changes in other forms.</p>
                <p>Before saving a form that will leave this page, review your other changes. If the browser warns about pending changes, cancel navigation to keep editing.</p>
            </div>
        </details>
    </section>

    <section class="card theme-help-section" aria-labelledby="help-troubleshoot-heading">
        <h2 id="help-troubleshoot-heading" class="h4">Troubleshooting</h2>
        <details>
            <summary>Why is the preview image or icon missing?</summary>
            <div class="theme-help-answer">
                <p>Cover images and icons come from the installed theme's package information. A missing icon uses the theme name's initial. Missing or broken cover images use the default preview image; if that also cannot load, a solid background remains.</p>
                <p>The cover image is separate from the live page preview.</p>
            </div>
        </details>
        <details>
            <summary>Why is customization or the preview unavailable?</summary>
            <div class="theme-help-answer">
                <p>Read the error shown on the theme page. Check whether the package is enabled, its dependencies are installed and compatible, and its theme definition is valid. For a parameterized route, provide every required parameter.</p>
                <p>Dependency badges distinguish missing, disabled, incompatible, and uncheckable requirements. Review package details before asking the theme author or site administrator for help.</p>
                <a href="{{ $customizationUrl }}?tab=themes">{{ $selectedTheme ? 'Review theme details' : 'Browse themes' }}</a>
            </div>
        </details>
        <details>
            <summary>When should I clear the cache?</summary>
            <div class="theme-help-answer">
                <p>Source cache normally refreshes automatically when theme JSON files change. Use Clear cache at the bottom of this page to rebuild cached sources if you suspect a stale source.</p>
                <p>Clearing this cache does not delete settings or presets, and does not activate a theme.</p>
            </div>
        </details>
    </section>
</div>
