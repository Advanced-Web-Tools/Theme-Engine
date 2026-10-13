@extends('Dashboard.views.templates.main')

@section('content')
    <link rel="stylesheet" href="/dashboard/customization/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/dashboard/customization/themes.css">

    <div>
        @include('customization/partials/header.awt.php')

        <div class="py-5 theme-customization-content">
            <div class="container">
                @include('customization/partials/notices.awt.php')

                @if($tab === 'help')
                    @include('customization/tabs/help.awt.php')
                @elseif($selectedTheme && $themeError && $tab !== 'themes')
                    <div class="alert alert-danger" role="alert">This theme cannot be customized: {{ $themeError }}</div>
                @elseif($selectedTheme && $tab === 'themes')
                    @include('customization/tabs/theme.awt.php')
                @elseif($tab === 'themes')
                    @include('customization/tabs/themes.awt.php')
                @elseif($tab === 'layout')
                    @include('customization/tabs/layout.awt.php')
                @elseif($tab === 'settings')
                    @include('customization/tabs/settings.awt.php')
                @elseif($tab === 'presets')
                    @include('customization/tabs/presets.awt.php')
                @endif

                @include('customization/partials/footnote.awt.php')
            </div>
        </div>
    </div>
    <script src="/dashboard/customization/themes.js" defer></script>
@endsection
