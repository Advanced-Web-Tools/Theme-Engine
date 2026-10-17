<?php

namespace ThemeEngine\service;

/** Ordered widget keys only; page overrides are independent for each sidebar. */
final class SidebarWidgetSettings
{
    public const SIDEBARS = ['side_l' => 'Left sidebar', 'side_r' => 'Right sidebar'];

    public function validate(array $input, array $available, array $previous = [], bool $page = false): array
    {
        if (array_diff_key($input, self::SIDEBARS)) {
            throw new \InvalidArgumentException('Widgets can only be placed in sidebars.');
        }
        $result = [];
        foreach (self::SIDEBARS as $sidebar => $label) {
            $keys = $input[$sidebar] ?? ($page ? null : []);
            if ($page && $keys === null) continue; // Missing/null means inherit.
            if (!is_array($keys) || !array_is_list($keys)) {
                throw new \InvalidArgumentException($label . ': invalid widget list (maximum 50).');
            }
            $keys = array_values(array_filter($keys, static fn($key) => $key !== ''));
            if (count($keys) > 50) {
                throw new \InvalidArgumentException($label . ': maximum 50 widgets.');
            }
            $result[$sidebar] = [];
            foreach ($keys as $key) {
                if (!is_string($key) || (!isset($available[$key]) && !in_array($key, $previous[$sidebar] ?? [], true))) {
                    throw new \InvalidArgumentException($label . ': unknown widget.');
                }
                if (in_array($key, $result[$sidebar], true)) {
                    throw new \InvalidArgumentException($label . ': a widget can only appear once.');
                }
                $result[$sidebar][] = $key;
            }
        }
        return $result;
    }
}
