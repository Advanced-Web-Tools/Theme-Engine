<h1>Installed themes</h1>
<p class="lead mb-4">Preview, customize, or activate a theme for your site. Each theme keeps its own settings.</p>

@if(count($themes) === 0)
    <div class="card empty-state">
        <h2 class="h3">No themes installed</h2>
        <span class="text-small text-muted">
            Install a package with type 2 to see it here.
        </span>
    </div>
@else
    <div class="card-grid theme-catalog">
        @foreach($themes as $theme)
            @include('customization/partials/theme-card.awt.php', ['theme' => $theme])
        @endforeach
    </div>
@endif
