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
    <div class="theme-preview-floating" aria-label="Preview controls">
        <div class="theme-preview-controls" role="group" aria-label="Preview device size">
            <?php foreach ($devices as $key => $_): ?>
                <button class="btn btn-outline-primary" type="button" data-preview-device="<?= $key ?>" aria-pressed="<?= $device === $key ? 'true' : 'false' ?>"><i class="fa-solid fa-<?= $icons[$key] ?> fa-fw" aria-hidden="true"></i> <?= ucfirst($key) ?></button>
            <?php endforeach; ?>
        </div>
        <div id="theme-preview-size" aria-live="polite"><?= $size ?></div>
    </div>
    <div class="theme-preview-viewport">
        <div id="theme-preview-device" class="theme-preview-device" data-initial-device="<?= $device ?>" style="--preview-width:<?= $widthCss ?>;--preview-height:<?= $heightCss ?>">
            <iframe class="theme-preview-frame" title="<?= $title ?>" src="<?= $url ?>" sandbox="allow-scripts allow-same-origin"></iframe>
        </div>
    </div>
    <script src="/dashboard/customization/themes.js" defer></script>
</body>
</html>
