<h1>Site appearance</h1>
<p class="lead mb-4">Set the browser tab title and favicon for all themes.</p>

<div class="theme-site-appearance">
    <form class="card content-panel" id="theme-site-appearance-form" method="post" action="/dashboard/customization/save" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ $token }}">
        <input type="hidden" name="operation" value="site_appearance">
        <input type="hidden" name="tab" value="site">

        <div class="form-field">
            <label class="form-label" for="theme-title-format">Browser title format</label>
            <select class="form-select" id="theme-title-format" name="title_format">
                @foreach($titleFormats as $format=>$label)
                    <option value="{{ $format }}" data-preview-title="{{ $titleExamples[$format] }}"
                            @if($format === $titleFormat) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label class="form-label" for="theme-favicon">Favicon</label>
            @if($faviconUrl)
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $faviconUrl }}" width="32" height="32" alt="">
                    <span>Current favicon</span>
                </div>
            @endif
            <input class="form-control" type="file" id="theme-favicon" name="favicon"
                   accept=".ico,.png,.webp,.gif" aria-describedby="theme-favicon-help">
            <small class="text-muted" id="theme-favicon-help">ICO, PNG, WebP, or GIF, up to 512 KiB and 512 × 512 pixels.</small>
            @if($faviconUrl)
                <label><input type="checkbox" name="remove_favicon" value="1"> Remove current favicon</label>
            @endif
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save site appearance</button>
        </div>
    </form>

    <section class="card theme-site-preview" aria-labelledby="theme-site-preview-heading"
             data-theme-site-preview data-saved-icon="{{ $faviconUrl ?? '' }}">
        <h2 class="h3" id="theme-site-preview-heading">Browser tab preview</h2>
        <p class="text-muted">See how the title and favicon look before saving.</p>

        <div class="theme-site-preview-bar">
            <div class="theme-site-preview-tab">
                <span class="theme-site-preview-icon" aria-hidden="true">
                    <i class="fa-solid fa-globe" data-site-icon-fallback @if($faviconUrl) hidden @endif></i>
                    <img width="16" height="16" alt="" data-site-preview-icon
                         @if($faviconUrl) src="{{ $faviconUrl }}" @else hidden @endif>
                </span>
                <span class="theme-site-preview-title" data-site-preview-title aria-live="polite"
                      title="{{ $titleExamples[$titleFormat] ?? $titleExamples['theme'] }}">{{ $titleExamples[$titleFormat] ?? $titleExamples['theme'] }}</span>
            </div>
        </div>
    </section>
</div>
