<header class="navbar border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
            <div class="navbar-brand">
                Customization
            </div>

            <div class="d-inline-flex align-items-center gap-1 text-small">
                <span class="status-dot" aria-hidden="true"></span>
                {{ $selectedTheme ? $selectedTheme->name : ($engine->hasActiveTheme() ? $engine->definition()->label : 'No active theme') }}
            </div>
        </div>

        @if($selectedTheme)
            <div class="theme-hero">
                <img class="theme-hero-image" data-preview-fallback="/dashboard/customization/default-preview.webp" src="{{ $selectedTheme->previewImage ?: '/dashboard/customization/default-preview.webp' }}" alt="">
                <div class="theme-hero-content">
                    <a class="theme-back" href="/dashboard/customization?tab=themes"><i class="fa-solid fa-arrow-left fa-fw" aria-hidden="true"></i> All themes</a>
                    <div class="theme-heading">
                        <div class="avatar-tile theme-icon-large" aria-hidden="true">
                            @if($selectedTheme->icon)
                                <img src="{{ $selectedTheme->icon }}" alt="">
                            @else
                                {{ strtoupper(substr($selectedTheme->name,0,1)) }}
                            @endif
                        </div>
                        <div>
                            <h1>{{ $selectedTheme->name }}</h1>
                            @if($engine->hasActiveTheme() && $engine->activeTheme()->packageId === $selectedTheme->packageId)
                                <span class="badge">Active theme</span>
                            @elseif(!$selectedTheme->enabled)
                                <span class="badge badge-muted">Package disabled</span>
                            @else
                                <span class="badge badge-muted">Available</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <nav class="nav-tabs" aria-label="Customization">
            @foreach(['themes'=>'Themes','layout'=>'Layout','settings'=>'Theme settings','presets'=>'Presets','help'=>'Help'] as $key=>$label)
                <a href="{{ $customizationUrl }}?tab={{ $key }}"
                   class="{{ $tab === $key ? 'active' : '' }}"
                   @if($tab === $key) aria-current="page" @endif>
                    <i class="fa-solid {{ $selectedTheme && $key === 'themes' ? 'fa-circle-info' : ['themes'=>'fa-palette','layout'=>'fa-table-columns','settings'=>'fa-sliders','presets'=>'fa-bookmark','help'=>'fa-circle-question'][$key] }} fa-fw" aria-hidden="true"></i>
                    {{ $selectedTheme && $key === 'themes' ? 'About theme' : $label }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
