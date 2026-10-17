<section class="card" aria-labelledby="theme-routes-heading">
    <h2 id="theme-routes-heading" class="h3">Available pages</h2>
    @if($themeError)
        <div class="alert alert-danger" role="alert">{{ $themeError }}</div>
    @elseif(count($themeRoutes) === 0)
        <p class="text-muted">This theme does not provide any pages.</p>
    @else
        <p class="text-muted">Choose a page to view. Enter any required details, such as an article ID.</p>
        <div class="theme-routes">
            @foreach($themeRoutes as $route)
                <form class="theme-route" method="get" action="/dashboard/customization/themes/{{ $selectedTheme->packageId }}/preview" target="_blank" rel="noopener noreferrer">
                    <input type="hidden" name="window" value="1">
                    <input type="hidden" name="route" value="{{ $route['index'] }}">
                    <div class="theme-route-label">
                        <strong>{{ $route['label'] }}</strong>
                    </div>
                    @foreach($route['parameters'] as $parameter)
                        <label class="theme-route-parameter">
                            <span class="text-small">{{ $parameter }}</span>
                            <input class="form-control" name="params[{{ $parameter }}]" maxlength="512" required>
                        </label>
                    @endforeach
                    <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-eye fa-fw" aria-hidden="true"></i> View page</button>
                </form>
            @endforeach
        </div>
    @endif
</section>
