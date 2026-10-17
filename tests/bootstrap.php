<?php

// Override these paths when the framework and URPC live outside the AWT checkout.
$framework = getenv('AWT_FRAMEWORK_CLASSES') ?: dirname(__DIR__, 3) . '/Advanced-Web-Tools-Framework/awt_src/classes';
$urpc = getenv('URPC_PACKAGE_ROOT') ?: dirname(__DIR__, 2) . '/URPC/URPC';
spl_autoload_register(static function (string $class) use ($framework, $urpc): void {
    $roots = ['ThemeEngine\\' => dirname(__DIR__) . '/ThemeEngine', 'URPC\\' => $urpc];
    foreach ($roots as $prefix => $root) {
        if (str_starts_with($class, $prefix)) {
            require $root . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            return;
        }
    }
    $file = $framework . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) require $file;
});

