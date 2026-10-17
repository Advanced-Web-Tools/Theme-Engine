@if(!($compactEditor ?? false))
<h1>Page and global layout</h1>
<p class="lead mb-4">
    Set the sections that appear across {{ $selectedTheme ? $selectedTheme->name : 'your active theme' }}.
    Individual pages can follow these defaults or use their own.
</p>
@endif

<div class="card-stack content-panel">
    <form class="card" method="post" action="/dashboard/customization/save">
        @include('customization/partials/form-context.awt.php')
        <input type="hidden" name="operation" value="layout">
        <input type="hidden" name="tab" value="layout">

        <h2 class="h4 mb-1">{{ ($compactEditor ?? false) ? 'Global defaults' : 'Global layout defaults' }}</h2>
        @if(!($compactEditor ?? false))
        <span class="text-small text-muted">Applies to all pages in this theme unless a page overrides it. Content is always included.</span>

        <div class="layout-diagram" aria-label="Five section layout">
            <span class="layout-wide">Header</span>
            <span>Left sidebar</span>
            <span class="layout-content">Content</span>
            <span>Right sidebar</span>
            <span class="layout-wide">Footer</span>
        </div>

        @endif

        @foreach(['header'=>'Header','footer'=>'Footer','side_l'=>'Left sidebar','side_r'=>'Right sidebar'] as $key=>$label)
            <div class="setting-row">
                <div class="min-w-0">
                    <h3 class="h5 mb-1">{{ $label }}</h3>
                    @if(!($compactEditor ?? false))
                    <p>Show {{ strtolower($label) }} by default.</p>
                    @endif
                </div>

                <label class="form-switch">
                    <input type="hidden" name="sections[{{ $key }}]" value="0">
                    <input aria-label="{{ $label }}"
                           type="checkbox"
                           name="sections[{{ $key }}]"
                           value="1"
                           @if($global[$key]) checked @endif>
                    <span class="switch-track" aria-hidden="true"></span>
                </label>
            </div>
        @endforeach

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save global layout</button>
        </div>
    </form>

    @if($definition)
        @foreach($definition->pages as $page)
            <form class="card" method="post" action="/dashboard/customization/save" data-preview-page-key="{{ $page->key }}">
                @include('customization/partials/form-context.awt.php')
                <input type="hidden" name="operation" value="page">
                <input type="hidden" name="page_key" value="{{ $page->key }}">
                <input type="hidden" name="tab" value="layout">

                <div class="page-heading mb-3">
                    <h2 class="h4 m-0">{{ $page->label }}</h2>
                    <span class="text-small text-muted">Only this page</span>
                </div>

                @foreach(['header'=>'Header','footer'=>'Footer','side_l'=>'Left sidebar','side_r'=>'Right sidebar'] as $key=>$label)
                    <div class="setting-row">
                        <label class="form-label" for="{{ $page->key }}-{{ $key }}">
                            {{ $label }}
                        </label>

                        <select class="form-select"
                                id="{{ $page->key }}-{{ $key }}"
                                name="sections[{{ $key }}]">
                            <option value="inherit"
                                    @if(!array_key_exists($key,$overrides[$page->key] ?? [])) selected @endif>
                                Use global default
                            </option>
                            <option value="on"
                                    @if(($overrides[$page->key][$key] ?? null) === true) selected @endif>
                                Show
                            </option>
                            <option value="off"
                                    @if(($overrides[$page->key][$key] ?? null) === false) selected @endif>
                                Hide
                            </option>
                        </select>
                    </div>
                @endforeach

                <div class="form-actions">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save page layout
                    </button>
                </div>
            </form>
        @endforeach
    @endif
</div>
