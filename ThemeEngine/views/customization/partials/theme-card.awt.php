<div class="card theme-card {{ $engine->hasActiveTheme() && $engine->activeTheme()->packageId === $theme->packageId ? 'is-active w-100' : '' }}">
    <a class="theme-card-cover" href="/dashboard/customization/themes/{{ $theme->packageId }}" aria-label="View {{ $theme->name }}">
        <img data-preview-fallback="/dashboard/customization/default-preview.webp" src="{{ $theme->previewImage ?: '/dashboard/customization/default-preview.webp' }}" alt="" loading="lazy">
    </a>
    <div class="card-header">
        <a class="avatar-tile" href="/dashboard/customization/themes/{{ $theme->packageId }}" aria-label="View {{ $theme->name }}">
            @if($theme->icon)
                <img src="{{ $theme->icon }}" alt="" loading="lazy">
            @else
                {{ strtoupper(substr($theme->name,0,1)) }}
            @endif
        </a>

        @if($engine->hasActiveTheme() && $engine->activeTheme()->packageId === $theme->packageId)
            <span class="badge">Active theme</span>
        @elseif(!$theme->enabled)
            <span class="badge badge-muted">Package disabled</span>
        @else
            <span class="badge badge-muted">Available</span>
        @endif
    </div>

    <h2 class="card-title"><a class="theme-card-link" href="/dashboard/customization/themes/{{ $theme->packageId }}">{{ $theme->name }}</a></h2>
    <p class="card-description">
        {{ $theme->description ?? 'A theme for your site.' }}
    </p>

    <div class="card-footer">
    <span class="text-small text-muted">
        Version {{ $theme->version }}
    </span>

        @if($engine->hasActiveTheme() && $engine->activeTheme()->packageId === $theme->packageId)
            <a class="btn btn-outline-primary"
               href="/dashboard/customization/themes/{{ $theme->packageId }}?tab=settings">
                <i class="fa-solid fa-sliders fa-fw" aria-hidden="true"></i> Customize
            </a>
        @else
            <form method="post" action="/dashboard/customization/save">
                @include('customization/partials/form-context.awt.php')
                <input type="hidden" name="operation" value="activate">
                <input type="hidden" name="package_id" value="{{ $theme->packageId }}">
                <input type="hidden" name="tab" value="themes">

                <button class="btn btn-primary" type="submit"
                        @if(!$theme->enabled) disabled @endif>
                    Activate theme
                </button>
            </form>
        @endif
    </div>
</div>
