<input type="hidden" name="_token" value="{{ $token }}">
@if($selectedTheme)
    <input type="hidden" name="theme_id" value="{{ $selectedTheme->packageId }}">
@endif
