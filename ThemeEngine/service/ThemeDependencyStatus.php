<?php
namespace ThemeEngine\service;

use package\dependency\Dependency;

final class ThemeDependencyStatus
{
    /** @param array<string,array> $installedPackages Installed packages keyed by package name. */
    public function describe(array $dependencies, array $installedPackages): array
    {
        foreach ($dependencies as &$dependency) {
            $package = $installedPackages[$dependency['name']] ?? null;
            $dependency['installed_version'] = $package['version'] ?? null;
            $dependency['enabled'] = $package !== null && (bool)($package['status'] ?? false);
            if ($package === null) {
                $status = 'missing';
            } else {
                try {
                    $compatible = Dependency::matchesVersion($package['version'], $dependency['version']);
                    $status = !$compatible ? 'incompatible' : ($dependency['enabled'] ? 'installed' : 'disabled');
                } catch (\InvalidArgumentException) {
                    $status = 'unknown';
                }
            }
            $dependency['status'] = $status;
            $dependency['status_label'] = match ($status) {
                'installed' => 'Installed and compatible',
                'missing' => 'Missing',
                'disabled' => 'Disabled',
                'incompatible' => 'Incompatible version',
                default => 'Version requirement could not be checked',
            };
        }
        return $dependencies;
    }
}
