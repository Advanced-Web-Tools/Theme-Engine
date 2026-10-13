<?php

namespace ThemeEngine\service\customization;

use ThemeEngine\api\ThemeRecord;

/** URLs shared by dashboard navigation, redirects and preview rendering. */
final class CustomizationUrls
{
    public const BASE = '/dashboard/customization';

    public static function dashboard(?ThemeRecord $theme = null): string
    {
        return $theme === null ? self::BASE : self::BASE . '/themes/' . $theme->packageId;
    }

    public static function preview(int $packageId, array $query = []): string
    {
        $url = self::BASE . '/themes/' . $packageId . '/preview';
        return $query === [] ? $url : $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
