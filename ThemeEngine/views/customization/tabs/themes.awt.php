<h1>A different look.<br>The same site.</h1>
<p class="lead mb-4">
    Choose one theme for your entire site. Each theme keeps its own settings,
    so switching feels effortless.
</p>

@if(count($themes) === 0)
    <div class="card empty-state">
        <div class="avatar-tile" aria-hidden="true"><i class="fa-solid fa-palette fa-fw" aria-hidden="true"></i></div>
        <h2 class="h3">Your next look starts here.</h2>
        <p>No theme packages are installed yet.</p>
        <span class="text-small text-muted">
        Install a package with type 2 to see it here.
    </span>
    </div>
@else
    <div class="card-grid">
        @foreach($themes as $theme)
            @include('customization/partials/theme-card.awt.php', ['theme' => $theme])
        @endforeach
    </div>
@endif
