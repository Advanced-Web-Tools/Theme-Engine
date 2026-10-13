<div id="theme-unsaved-indicator" class="theme-unsaved-indicator" role="status" aria-live="polite" hidden>
    <span>You have unsaved changes. Save each changed form before leaving this page.</span>
    <div class="theme-unsaved-actions" data-unsaved-actions></div>
</div>

@if($saved)
    <div class="alert alert-success" role="status">
        Your changes have been saved.
    </div>
@endif

@if($error)
    <div class="alert alert-danger" role="alert">{{ $error }}</div>
@endif

@if($engine->loadError)
    <div class="alert alert-danger" role="alert">
        The selected theme could not be loaded: {{ $engine->loadError }}.
        You can select another theme below.
    </div>
@endif

@if($selectedTheme && $tab !== 'themes' && $tab !== 'help')
    <p class="text-small text-muted">Editing {{ $selectedTheme->name }}. These changes apply when this theme is active.</p>
@endif
