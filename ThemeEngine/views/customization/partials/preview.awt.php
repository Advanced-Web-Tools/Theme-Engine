<section class="card theme-preview-panel" aria-labelledby="theme-preview-heading">
    <h2 id="theme-preview-heading" class="h3">Theme preview</h2>
    <p class="text-muted">Explore this theme with its saved settings before activating it.</p>
    @if($themeError || count($themeRoutes) === 0)
        <p>Preview is unavailable for this theme.</p>
    @else
        @if(!$previewUrl)
            <p>Enter the route parameters above and choose Preview route to begin.</p>
        @endif
        <div class="theme-preview-controls" role="group" aria-label="Preview device size">
            <button class="btn btn-outline-primary" type="button" data-preview-device="desktop" aria-pressed="true"><i class="fa-solid fa-desktop fa-fw" aria-hidden="true"></i> Desktop</button>
            <button class="btn btn-outline-primary" type="button" data-preview-device="tablet" aria-pressed="false"><i class="fa-solid fa-tablet-screen-button fa-fw" aria-hidden="true"></i> Tablet</button>
            <button class="btn btn-outline-primary" type="button" data-preview-device="mobile" aria-pressed="false"><i class="fa-solid fa-mobile-screen-button fa-fw" aria-hidden="true"></i> Mobile</button>
            <span id="theme-preview-size" class="text-small text-muted" aria-live="polite">Full size</span>
        </div>
        <div class="theme-preview-stage">
            <a id="theme-preview-open" class="btn btn-primary theme-preview-open"
               href="{{ $previewUrl ? $previewUrl . '&window=1&device=desktop' : 'about:blank' }}" target="_blank" rel="noopener noreferrer"
               @if(!$previewUrl) aria-disabled="true" tabindex="-1" @endif>
                <i class="fa-solid fa-arrow-up-right-from-square fa-fw" aria-hidden="true"></i> Open in new window
            </a>
            <div class="theme-preview-viewport">
                <div id="theme-preview-device" class="theme-preview-device">
                    <iframe class="theme-preview-frame" name="theme-preview" title="{{ $selectedTheme->name }} preview" src="{{ $previewUrl ?? 'about:blank' }}" sandbox="allow-scripts allow-same-origin" loading="lazy"></iframe>
                </div>
            </div>
        </div>

    @endif
</section>
