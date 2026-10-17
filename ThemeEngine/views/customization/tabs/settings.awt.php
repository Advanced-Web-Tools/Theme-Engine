@if(!($compactEditor ?? false))
<h1>Global theme settings</h1>
<p class="lead mb-4">
    These settings apply across all pages in {{ $selectedTheme ? $selectedTheme->name : 'your active theme' }}.
</p>
@endif

@if(!$definition)
    <div class="card empty-state">
        <h2 class="h3">Choose a theme first.</h2>
        <p>Theme settings appear here after activation.</p>
        <a class="btn btn-primary" href="/dashboard/customization?tab=themes">
            <i class="fa-solid fa-palette fa-fw" aria-hidden="true"></i> Browse themes
        </a>
    </div>
@elseif(count($definition->settings) === 0)
    <div class="card empty-state">
        @if(!($compactEditor ?? false))
        <h2 class="h3">{{ $definition->label }} is ready to go.</h2>
        @endif
        <p>This theme does not provide additional settings.</p>
    </div>
@else
    <form class="card content-panel"
          method="post"
          action="/dashboard/customization/save">
        @include('customization/partials/form-context.awt.php')
        <input type="hidden" name="operation" value="settings">
        <input type="hidden" name="tab" value="settings">

        @if(!($compactEditor ?? false))
        <h2 class="h4 mb-1">{{ $definition->label }}</h2>
        <span class="text-small text-muted">
        Changes are saved for all pages in {{ $definition->label }}.
    </span>
        @endif

        @foreach($definition->settings as $key=>$field)
            @include('customization/partials/setting-field.awt.php', ['key' => $key, 'field' => $field])
        @endforeach

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save settings</button>
        </div>
    </form>

    <form method="post"
          action="/dashboard/customization/save"
          class="form-actions content-panel">
        @include('customization/partials/form-context.awt.php')
        <input type="hidden" name="operation" value="reset_settings">
        <input type="hidden" name="tab" value="settings">

        <button class="btn btn-outline-secondary" type="submit">
            <i class="fa-solid fa-rotate-left fa-fw" aria-hidden="true"></i> Restore theme defaults
        </button>
    </form>
@endif
