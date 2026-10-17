<div data-preview-page-routes="{{ json_encode($previewPageRoutes) }}" hidden></div>
<nav class="nav-tabs theme-preview-settings-tabs" role="tablist" aria-label="Theme customization">
    <a href="#preview-panel-settings" id="preview-tab-settings" class="active" role="tab" aria-controls="preview-panel-settings" aria-selected="true" data-preview-tab="settings"><i class="fa-solid fa-sliders fa-fw" aria-hidden="true"></i> Global settings</a>
    <a href="#preview-panel-layout" id="preview-tab-layout" role="tab" aria-controls="preview-panel-layout" aria-selected="false" tabindex="-1" data-preview-tab="layout"><i class="fa-solid fa-table-columns fa-fw" aria-hidden="true"></i> Layout</a>
    <a href="#preview-panel-presets" id="preview-tab-presets" role="tab" aria-controls="preview-panel-presets" aria-selected="false" tabindex="-1" data-preview-tab="presets"><i class="fa-solid fa-bookmark fa-fw" aria-hidden="true"></i> Presets</a>
</nav>
<div class="theme-preview-settings-content theme-customization-content">
    <div id="preview-panel-settings" role="tabpanel" aria-labelledby="preview-tab-settings" data-preview-panel="settings">
        @include('customization/tabs/settings.awt.php', ['compactEditor' => true])
    </div>
    <div id="preview-panel-layout" role="tabpanel" aria-labelledby="preview-tab-layout" data-preview-panel="layout" hidden>
        <p data-preview-page-note hidden>Page layout unavailable.</p>
        @include('customization/tabs/layout.awt.php', ['compactEditor' => true])
    </div>
    <div id="preview-panel-presets" role="tabpanel" aria-labelledby="preview-tab-presets" data-preview-panel="presets" hidden>
        @include('customization/tabs/presets.awt.php', ['compactEditor' => true])
    </div>
</div>
