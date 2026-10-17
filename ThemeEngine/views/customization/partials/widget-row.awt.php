<div class="theme-widget-row" data-widget-row>
    <select class="form-select" name="widgets[{{ $sidebar }}][]" aria-label="{{ $sidebarLabel }} widget">
        <option value="">Choose widget</option>
        @if($widgetKey !== '' && !isset($availableWidgets[$widgetKey]))
            <option value="{{ $widgetKey }}" selected>Unavailable: {{ $widgetKey }}</option>
        @endif
        @foreach($availableWidgets as $widget)
            <option value="{{ $widget->key }}" @if($widgetKey === $widget->key) selected @endif>{{ $widget->label }}</option>
        @endforeach
    </select>
    <button type="button" class="btn btn-outline-secondary" data-widget-move="up" aria-label="Move widget up" title="Move up"><i class="fa-solid fa-arrow-up fa-fw" aria-hidden="true"></i></button>
    <button type="button" class="btn btn-outline-secondary" data-widget-move="down" aria-label="Move widget down" title="Move down"><i class="fa-solid fa-arrow-down fa-fw" aria-hidden="true"></i></button>
    <button type="button" class="btn btn-outline-secondary" data-widget-remove aria-label="Remove widget" title="Remove"><i class="fa-solid fa-xmark fa-fw" aria-hidden="true"></i></button>
</div>
