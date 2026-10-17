@if(!($compactEditor ?? false))
<h1>Theme presets</h1>
<p class="lead mb-4">
    Presets capture theme settings, layouts, page overrides, and sidebar widgets.
    Applying a preset replaces the saved configuration across all pages in this theme.
</p>
@endif

@if(!$definition)
    <div class="card empty-state">
        <h2 class="h3">Choose a theme first.</h2>
        <p>Presets belong to the theme they were saved with.</p>
        <a class="btn btn-primary" href="/dashboard/customization?tab=themes">
            <i class="fa-solid fa-palette fa-fw" aria-hidden="true"></i> Browse themes
        </a>
    </div>
@else
    @if($compactEditor ?? false)
        <p class="text-small text-muted">Applying a preset replaces settings, layouts, and widgets for all pages.</p>
    @endif
    <div class="card-stack content-panel">
        <form class="card" method="post" action="/dashboard/customization/save">
            @include('customization/partials/form-context.awt.php')
            <input type="hidden" name="operation" value="preset_save">
            <input type="hidden" name="tab" value="presets">

            <h2 class="h4 mb-1">{{ ($compactEditor ?? false) ? 'New preset' : 'Remember this look' }}</h2>
            @if(!($compactEditor ?? false))
            <span class="text-small text-muted">
            Save the current configuration of {{ $definition->label }}.
        </span>
            @endif

            <div class="inline-form">
                <input class="form-control"
                       type="text"
                       name="preset_name"
                       aria-label="Preset name"
                       placeholder="Give your preset a name"
                       maxlength="120"
                       required>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-bookmark fa-fw" aria-hidden="true"></i> Save preset</button>
            </div>
        </form>

        @foreach($presets as $preset)
            <div class="card d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="min-w-0">
                    <h3 class="h5 mb-1">{{ $preset['name'] }}</h3>
                    @if(!($compactEditor ?? false))
                    <span class="text-small text-muted">{{ $definition->label }} preset</span>
                    @endif
                </div>

                <div class="form-actions m-0">
                    <form method="post" action="/dashboard/customization/save">
                        @include('customization/partials/form-context.awt.php')
                        <input type="hidden" name="operation" value="preset_apply">
                        <input type="hidden" name="preset_id" value="{{ $preset['id'] }}">
                        <input type="hidden" name="tab" value="presets">

                        <button class="btn btn-primary" type="submit">Apply</button>
                    </form>

                    <form method="post" action="/dashboard/customization/save">
                        @include('customization/partials/form-context.awt.php')
                        <input type="hidden" name="operation" value="preset_delete">
                        <input type="hidden" name="preset_id" value="{{ $preset['id'] }}">
                        <input type="hidden" name="tab" value="presets">

                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-trash-can fa-fw" aria-hidden="true"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif
