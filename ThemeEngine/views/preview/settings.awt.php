<div class="theme-preview-settings-tabs" role="tablist" aria-label="Theme customization">
    <button type="button" id="preview-tab-settings" role="tab" aria-controls="preview-panel-settings" aria-selected="true" data-preview-tab="settings">Theme settings</button>
    <button type="button" id="preview-tab-layout" role="tab" aria-controls="preview-panel-layout" aria-selected="false" tabindex="-1" data-preview-tab="layout">Layout</button>
    <button type="button" id="preview-tab-presets" role="tab" aria-controls="preview-panel-presets" aria-selected="false" tabindex="-1" data-preview-tab="presets">Presets</button>
</div>
<div class="theme-preview-settings-content theme-customization-content">
    <div id="preview-panel-settings" role="tabpanel" aria-labelledby="preview-tab-settings" data-preview-panel="settings">
        @include('customization/tabs/settings.awt.php')
    </div>
    <div id="preview-panel-layout" role="tabpanel" aria-labelledby="preview-tab-layout" data-preview-panel="layout" hidden>
        @include('customization/tabs/layout.awt.php')
    </div>
    <div id="preview-panel-presets" role="tabpanel" aria-labelledby="preview-tab-presets" data-preview-panel="presets" hidden>
        @include('customization/tabs/presets.awt.php')
    </div>
</div>
