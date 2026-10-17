@if(!($compactEditor ?? false))
    <h1>Sidebar widgets</h1>
    <p class="lead mb-4">Choose widgets for your theme or individual pages.</p>
@endif

@if(!$definition)
    <div class="card empty-state"><p>Choose a theme to manage its widgets.</p></div>
@else
    @if(count($availableWidgets) === 0)
        <p class="text-muted">No sidebar widgets are registered.</p>
    @endif
    <p class="text-small text-muted">Sidebars must be enabled in Layout.</p>
    <div class="card-stack content-panel">
        <form class="card" method="post" action="/dashboard/customization/save">
            @include('customization/partials/form-context.awt.php')
            <input type="hidden" name="operation" value="widgets">
            <input type="hidden" name="tab" value="widgets">
            <h2 class="h4">All pages</h2>
            @include('customization/partials/widget-sidebars.awt.php', ['widgetSelections' => $globalWidgets, 'widgetPageKey' => null])
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save theme widgets</button>
            </div>
        </form>
        @foreach($definition->pages as $page)
            <form class="card" method="post" action="/dashboard/customization/save" data-preview-page-key="{{ $page->key }}">
                @include('customization/partials/form-context.awt.php')
                <input type="hidden" name="operation" value="page_widgets">
                <input type="hidden" name="page_key" value="{{ $page->key }}">
                <input type="hidden" name="tab" value="widgets">
                <div class="page-heading">
                    <h2 class="h4">{{ $page->label }}</h2>
                    <span class="text-small text-muted">Only this page</span>
                </div>
                @include('customization/partials/widget-sidebars.awt.php', ['widgetSelections' => $pageWidgets[$page->key] ?? [], 'widgetPageKey' => $page->key])
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save page widgets</button>
                </div>
            </form>
        @endforeach
    </div>
@endif
