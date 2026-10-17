@foreach(['side_l' => 'Left sidebar', 'side_r' => 'Right sidebar'] as $sidebar => $sidebarLabel)
    <section class="theme-widget-sidebar" data-widget-sidebar>
        <h3 class="h5">{{ $sidebarLabel }}</h3>
        @if($widgetPageKey !== null)
            <label class="form-label" for="widgets-{{ $widgetPageKey }}-{{ $sidebar }}">Widget selection</label>
            <select class="form-select" id="widgets-{{ $widgetPageKey }}-{{ $sidebar }}" name="widget_mode[{{ $sidebar }}]" data-widget-mode>
                <option value="inherit" @if(!array_key_exists($sidebar, $widgetSelections)) selected @endif>Use theme widgets</option>
                <option value="custom" @if(array_key_exists($sidebar, $widgetSelections)) selected @endif>Choose for this page</option>
            </select>
        @endif
        <fieldset class="theme-widget-list" aria-label="{{ $sidebarLabel }} widgets" data-widget-list @if($widgetPageKey !== null && !array_key_exists($sidebar, $widgetSelections)) hidden disabled @endif>
            <div class="theme-widget-rows" data-widget-rows>
                @foreach(array_merge($widgetSelections[$sidebar] ?? [], ['']) as $widgetKey)
                    @include('customization/partials/widget-row.awt.php', ['sidebar' => $sidebar, 'sidebarLabel' => $sidebarLabel, 'widgetKey' => $widgetKey])
                @endforeach
            </div>
            <template data-widget-row-template>
                @include('customization/partials/widget-row.awt.php', ['sidebar' => $sidebar, 'sidebarLabel' => $sidebarLabel, 'widgetKey' => ''])
            </template>
            <button type="button" class="btn btn-outline-secondary" data-widget-add @if(count($availableWidgets) === 0) disabled @endif><i class="fa-solid fa-plus fa-fw" aria-hidden="true"></i> Add widget</button>
        </fieldset>
    </section>
@endforeach
