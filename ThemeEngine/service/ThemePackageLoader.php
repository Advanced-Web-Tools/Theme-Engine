<?php

namespace ThemeEngine\service;

use database\interface\IProvider;
use PDO;
use ThemeEngine\api\ThemeRecord;

/** Converts installed package metadata into theme records and dependency status. */
final class ThemePackageLoader
{
    public function __construct(
        private readonly IProvider $provider,
        private readonly string $packageRoot,
    ) {}

    public function load(): ThemeRegistry
    {
        $statement = $this->provider->execute(
            'SELECT id, type, name, version, status, description, icon, preview_image, dependencies, author, license, license_url, minimum_awt_version, maximum_awt_version, installation_date FROM awt_package',
        );
        try {
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $statement->closeCursor();
        }

        $registry = new ThemeRegistry();
        $installedPackages = array_column($rows, null, 'name');
        $dependencyStatus = new ThemeDependencyStatus();
        foreach ($rows as $row) {
            if ((int) $row['type'] !== 2) {
                continue;
            }
            $registry->register(new ThemeRecord(
                (int) $row['id'],
                $row['name'],
                $this->packageRoot . str_replace(' ', '', $row['name']),
                $row['version'],
                (bool) $row['status'],
                $row['description'],
                self::assetUrl($row['icon'] ?? null),
                self::assetUrl($row['preview_image'] ?? null),
                $dependencyStatus->describe(self::dependencies($row['dependencies'] ?? null), $installedPackages),
                $row['author'] ?? null,
                $row['license'] ?? null,
                self::assetUrl($row['license_url'] ?? null),
                $row['minimum_awt_version'] ?? null,
                $row['maximum_awt_version'] ?? null,
                $row['installation_date'] ?? null,
            ));
        }
        return $registry;
    }

    private static function assetUrl(?string $url): ?string
    {
        if ($url === null || $url === '' || preg_match('/[\x00-\x20]/', $url) || str_starts_with($url, '//')) {
            return null;
        }
        return str_starts_with($url, '/') || preg_match('~^https?://~i', $url) ? $url : null;
    }

    private static function dependencies(array|string|null $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (!is_array($value)) {
            return [];
        }
        $dependencies = [];
        foreach ($value as $dependency) {
            if (!is_array($dependency) || !is_string($dependency['name'] ?? null) || !is_string($dependency['version'] ?? null)) {
                continue;
            }
            $dependencies[] = [
                'name' => $dependency['name'],
                'version' => $dependency['version'],
                'url' => is_string($dependency['url'] ?? null) ? self::assetUrl($dependency['url']) : null,
            ];
        }
        return $dependencies;
    }
}
