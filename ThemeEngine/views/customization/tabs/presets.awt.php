<h1>Save a little inspiration.</h1>
<p class="lead mb-4">
    Presets capture theme settings, the default layout and page overrides.
    Apply one whenever you want that look back.
</p>

@if(!$definition)
    <div class="card empty-state">
        <h2 class="h3">Choose a theme first.</h2>
        <p>Presets belong to the theme they were saved with.</p>
        <a class="btn btn-primary" href="/dashboard/customization?tab=themes">
            <i class="fa-solid fa-palette fa-fw" aria-hidden="true"></i> Browse themes
        </a>
    </div>
@else
    <div class="card-stack content-panel">
        <form class="card" method="post" action="/dashboard/customization/save">
            @include('customization/partials/form-context.awt.php')
            <input type="hidden" name="operation" value="preset_save">
            <input type="hidden" name="tab" value="presets">

            <h2 class="h4 mb-1">Remember this look</h2>
            <span class="text-small text-muted">
            Save the current configuration of {{ $definition->label }}.
        </span>

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
                    <span class="text-small text-muted">
                    {{ $definition->label }} preset
                </span>
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
