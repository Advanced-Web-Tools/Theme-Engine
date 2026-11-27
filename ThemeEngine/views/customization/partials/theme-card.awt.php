@php
    $isActive = $engine->hasActiveTheme() && $engine->activeTheme()->packageId === $theme->packageId;
    $themeUrl = '/dashboard/customization/themes/' . $theme->packageId;
@endphp

<article class="card theme-card {{ $isActive ? 'is-active w-100' : '' }}">
    <div class="theme-card-cover">
        <img data-preview-fallback="/dashboard/customization/default-preview.webp" src="{{ $theme->previewImage ?: '/dashboard/customization/default-preview.webp' }}" alt="" loading="lazy">
    </div>

    <div class="theme-card-content">
        <div class="card-header">
            <div class="avatar-tile" aria-hidden="true">
                @if($theme->icon)
                    <img src="{{ $theme->icon }}" alt="" loading="lazy">
                @else
                    {{ strtoupper(substr($theme->name, 0, 1)) }}
                @endif
            </div>

            <div class="theme-card-heading">
                <h2 class="card-title"><a class="theme-card-link" href="{{ $themeUrl }}">{{ $theme->name }}</a></h2>
                @if($isActive)
                    <span class="theme-status"><span class="status-dot" aria-hidden="true"></span> Active theme</span>
                @elseif(!$theme->enabled)
                    <span class="theme-status">Package disabled</span>
                @else
                    <span class="theme-status">Available</span>
                @endif
            </div>
        </div>

        <p class="card-description">{{ $theme->description ?? 'A theme for your site.' }}</p>

        <div class="card-footer">
            <span class="text-small text-muted">Version {{ $theme->version }}</span>

            @if($isActive)
                <a class="btn btn-primary" href="{{ $themeUrl }}?tab=settings">
                    <i class="fa-solid fa-sliders fa-fw" aria-hidden="true"></i> Customize
                </a>
            @else
                <form method="post" action="/dashboard/customization/save">
                    @include('customization/partials/form-context.awt.php')
                    <input type="hidden" name="operation" value="activate">
                    <input type="hidden" name="package_id" value="{{ $theme->packageId }}">
                    <input type="hidden" name="tab" value="themes">

                    <button class="btn btn-outline-secondary" type="submit"
                            @if(!$theme->enabled) disabled @endif>
                        Activate theme
                    </button>
                </form>
            @endif
        </div>
    </div>
</article>
