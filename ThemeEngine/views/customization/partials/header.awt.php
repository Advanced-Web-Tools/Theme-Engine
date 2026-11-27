<header class="theme-customization-header border-bottom">
    <div class="container">
        <div class="theme-customization-bar">
            <div class="navbar-brand">Customization</div>

            @if(!$selectedTheme)
                <div class="theme-status">
                    @if($engine->hasActiveTheme())
                        <span class="status-dot" aria-hidden="true"></span>
                        <span>Active theme: {{ $engine->definition()->label }}</span>
                    @else
                        <span>No active theme</span>
                    @endif
                </div>
            @endif
        </div>

        @if($selectedTheme)
            <div class="theme-hero">
                <div class="theme-hero-content">
                    <a class="theme-back" href="/dashboard/customization?tab=themes"><i class="fa-solid fa-arrow-left fa-fw" aria-hidden="true"></i> All themes</a>
                    <div class="theme-heading">
                        <div class="avatar-tile" aria-hidden="true">
                            @if($selectedTheme->icon)
                                <img src="{{ $selectedTheme->icon }}" alt="">
                            @else
                                {{ strtoupper(substr($selectedTheme->name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <h1>{{ $selectedTheme->name }}</h1>
                            @if($engine->hasActiveTheme() && $engine->activeTheme()->packageId === $selectedTheme->packageId)
                                <span class="theme-status"><span class="status-dot" aria-hidden="true"></span> Active theme</span>
                            @elseif(!$selectedTheme->enabled)
                                <span class="theme-status">Package disabled</span>
                            @else
                                <span class="theme-status">Available</span>
                            @endif
                        </div>
                    </div>
                </div>
                <img class="theme-hero-image" data-preview-fallback="/dashboard/customization/default-preview.webp" src="{{ $selectedTheme->previewImage ?: '/dashboard/customization/default-preview.webp' }}" alt="">
            </div>
        @endif

        <nav class="nav-tabs" aria-label="Customization">
            @foreach(['themes'=>'Themes','site'=>'Site appearance','layout'=>'Layout','settings'=>'Theme settings','widgets'=>'Widgets','presets'=>'Presets','help'=>'Help'] as $key=>$label)
                <a href="{{ $key === 'site' ? '/dashboard/customization' : $customizationUrl }}?tab={{ $key }}"
                   class="{{ $tab === $key ? 'active' : '' }}"
                   @if($tab === $key) aria-current="page" @endif>
                    {{ $selectedTheme && $key === 'themes' ? 'About theme' : $label }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
