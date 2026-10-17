<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    <script src="/dashboard/customization/preview-theme.js"></script>
    <link rel="stylesheet" href="/dashboard/customization/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/dashboard/customization/themes.css">
</head>
<body class="theme-preview-window">
    <?php if ($settingsUrl !== null): ?>
        <button id="theme-preview-settings-toggle" class="theme-preview-settings-launcher" type="button" aria-label="Edit theme" title="Edit theme" aria-expanded="false" aria-controls="theme-preview-settings-popup"><i class="fa-solid fa-pen fa-fw" aria-hidden="true"></i> Edit</button>
        <section id="theme-preview-settings-popup" class="theme-preview-settings-popup" aria-label="Theme customization" data-settings-url="<?= $settingsUrl ?>" inert>
            <div class="theme-preview-settings-heading">
                <strong>Theme customization</strong>
                <button type="button" data-preview-settings-close aria-label="Close theme settings"><i class="fa-solid fa-xmark fa-fw" aria-hidden="true"></i></button>
            </div>
            <p class="theme-preview-settings-status" role="status" aria-live="polite"></p>
            <div data-preview-settings-body></div>
        </section>
    <?php endif; ?>
    <div class="theme-preview-floating" role="group" aria-label="Preview device size">
        <?php foreach ($devices as $key => $_): ?>
            <button type="button" data-preview-device="<?= $key ?>" aria-label="<?= ucfirst($key) ?> preview" title="<?= ucfirst($key) ?> preview" aria-pressed="<?= $device === $key ? 'true' : 'false' ?>"><i class="fa-solid fa-<?= $icons[$key] ?> fa-fw" aria-hidden="true"></i></button>
        <?php endforeach; ?>
    </div>
    <div class="theme-preview-viewport">
        <div id="theme-preview-device" class="theme-preview-device" data-initial-device="<?= $device ?>" style="--preview-width:<?= $widthCss ?>;--preview-height:<?= $heightCss ?>">
            <iframe class="theme-preview-frame" title="<?= $title ?>" src="<?= $url ?>" sandbox="allow-scripts allow-same-origin"></iframe>
        </div>
    </div>
    <script src="/dashboard/customization/themes.js" defer></script>
</body>
</html>
