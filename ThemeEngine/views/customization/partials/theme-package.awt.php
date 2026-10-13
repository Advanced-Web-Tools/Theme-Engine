<section class="card" aria-labelledby="theme-package-heading">
    <div class="page-heading">
        <h2 id="theme-package-heading" class="h3">About this theme</h2>
        <span class="text-small text-muted">Version {{ $selectedTheme->version }}</span>
    </div>
    <p class="theme-package-description">{{ $selectedTheme->description ?: 'A theme for your site.' }}</p>
    <dl class="theme-package-details">
        <div>
            <dt>Author</dt>
            <dd>{{ $selectedTheme->author ?: 'Not specified' }}</dd>
        </div>
        <div>
            <dt>License</dt>
            <dd>
                @if($selectedTheme->licenseUrl)
                    <a href="{{ $selectedTheme->licenseUrl }}" target="_blank" rel="noopener noreferrer">{{ $selectedTheme->license ?: 'View license' }} <i class="fa-solid fa-arrow-up-right-from-square fa-fw" aria-hidden="true"></i></a>
                @else
                    {{ $selectedTheme->license ?: 'Not specified' }}
                @endif
            </dd>
        </div>
        <div>
            <dt>AWT compatibility</dt>
            <dd>
                @if($selectedTheme->minimumAwtVersion && $selectedTheme->maximumAwtVersion)
                    {{ $selectedTheme->minimumAwtVersion }} – {{ $selectedTheme->maximumAwtVersion }}
                @elseif($selectedTheme->minimumAwtVersion)
                    {{ $selectedTheme->minimumAwtVersion }} or later
                @elseif($selectedTheme->maximumAwtVersion)
                    Up to {{ $selectedTheme->maximumAwtVersion }}
                @else
                    Not specified
                @endif
            </dd>
        </div>
        <div>
            <dt>Installed</dt>
            <dd>
                @if($selectedTheme->installedAt)
                    <time datetime="{{ str_replace(' ', 'T', $selectedTheme->installedAt) }}">{{ $selectedTheme->installedAt }}</time>
                @else
                    Not specified
                @endif
            </dd>
        </div>
        <div>
            <dt>Package status</dt>
            <dd>{{ $selectedTheme->enabled ? 'Enabled' : 'Disabled' }}</dd>
        </div>
        <div>
            <dt>Used on this site</dt>
            <dd>{{ $engine->hasActiveTheme() && $engine->activeTheme()->packageId === $selectedTheme->packageId ? 'Active theme' : 'Not active' }}</dd>
        </div>
    </dl>
    <h3 class="h5">Dependencies</h3>
    @if(count($selectedTheme->dependencies) === 0)
        <p class="text-muted">No package dependencies.</p>
    @else
        <ul class="theme-dependencies">
            @foreach($selectedTheme->dependencies as $dependency)
                <li>
                    <span>{{ $dependency['name'] }}</span> <code>{{ $dependency['version'] }}</code>
                    <span class="badge theme-dependency-status theme-dependency-{{ $dependency['status'] ?? 'unknown' }}">{{ $dependency['status_label'] ?? 'Not checked' }}</span>
                    @if(!empty($dependency['installed_version']))
                        <span class="text-small text-muted">Installed {{ $dependency['installed_version'] }}</span>
                    @endif
                    @if(isset($dependency['enabled']) && !$dependency['enabled'] && !empty($dependency['installed_version']) && ($dependency['status'] ?? '') !== 'disabled')
                        <span class="badge badge-muted">Package disabled</span>
                    @endif
                    @if(!empty($dependency['url']))
                        <a class="text-small" href="{{ $dependency['url'] }}" target="_blank" rel="noopener noreferrer">Package source <i class="fa-solid fa-arrow-up-right-from-square fa-fw" aria-hidden="true"></i></a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    <div class="form-actions">
        @if($engine->hasActiveTheme() && $engine->activeTheme()->packageId === $selectedTheme->packageId)
            <a class="btn btn-primary" href="{{ $customizationUrl }}?tab=settings"><i class="fa-solid fa-sliders fa-fw" aria-hidden="true"></i> Customize theme</a>
        @else
            <form method="post" action="/dashboard/customization/save">
                @include('customization/partials/form-context.awt.php')
                <input type="hidden" name="operation" value="activate">
                <input type="hidden" name="package_id" value="{{ $selectedTheme->packageId }}">
                <input type="hidden" name="tab" value="themes">
                <button class="btn btn-primary" type="submit" @if(!$selectedTheme->enabled || $themeError) disabled @endif>Activate theme</button>
            </form>
        @endif
    </div>
</section>
