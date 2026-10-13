<?php

namespace ThemeEngine\service;

/** Validates schema declarations, stored values and submitted theme settings. */
final class SettingsSchema
{
    private const TYPES = ['text', 'textarea', 'boolean', 'number', 'select', 'color', 'url'];

    public function validateDefinition(array $schema): void
    {
        foreach ($schema as $key => $field) {
            if (!is_string($key) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $key) || !is_array($field)
                || !in_array($field['type'] ?? '', self::TYPES, true) || !is_string($field['label'] ?? null)
                || !array_key_exists('default', $field)) {
                throw new \InvalidArgumentException('Invalid setting definition: ' . $key);
            }
            if ($field['type'] === 'select' && (!is_array($field['options'] ?? null) || $field['options'] === [])) {
                throw new \InvalidArgumentException('Select options are required.');
            }
            foreach (['min', 'max', 'max_length'] as $limit) {
                if (isset($field[$limit]) && !is_numeric($field[$limit])) {
                    throw new \InvalidArgumentException('Invalid setting limit.');
                }
            }
            $this->normalize($field, $field['default']);
        }
    }

    public function values(array $schema, array $stored): array
    {
        $values = [];
        foreach ($schema as $key => $field) {
            try {
                $values[$key] = $this->normalize($field, $stored[$key] ?? $field['default']);
            } catch (\InvalidArgumentException) {
                $values[$key] = $this->normalize($field, $field['default']);
            }
        }
        return $values;
    }

    public function submitted(array $schema, array $input): array
    {
        if (array_diff_key($input, $schema)) {
            throw new \InvalidArgumentException('Unknown theme setting.');
        }
        $result = [];
        foreach ($schema as $key => $field) {
            if (!array_key_exists($key, $input) && $field['type'] !== 'boolean') {
                throw new \InvalidArgumentException('Missing setting: ' . $field['label']);
            }
            try {
                $result[$key] = $this->normalize($field, $input[$key] ?? false);
            } catch (\InvalidArgumentException $exception) {
                throw new \InvalidArgumentException($field['label'] . ': ' . $exception->getMessage());
            }
        }
        return $result;
    }

    private function normalize(array $field, mixed $value): mixed
    {
        $type = $field['type'];
        if ($type === 'boolean') {
            if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                throw new \InvalidArgumentException('Expected a boolean.');
            }
            return $value === true || $value === 1 || $value === '1';
        }
        if ($type === 'number') {
            if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value) || !is_finite((float) $value)) {
                throw new \InvalidArgumentException('Expected a number.');
            }
            $value = (float) $value;
            if ((isset($field['min']) && $value < $field['min']) || (isset($field['max']) && $value > $field['max'])) {
                throw new \InvalidArgumentException('Number is outside the allowed range.');
            }
            return $value;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Expected text.');
        }
        if (strlen($value) > ($field['max_length'] ?? 4096)) {
            throw new \InvalidArgumentException('Text is too long.');
        }
        if (($field['required'] ?? false) && trim($value) === '') {
            throw new \InvalidArgumentException('A value is required.');
        }
        if ($type === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/D', $value)) {
            throw new \InvalidArgumentException('Expected #RRGGBB.');
        }
        if ($type === 'select' && !array_key_exists($value, $field['options'])) {
            throw new \InvalidArgumentException('Unknown selection.');
        }
        if ($type === 'url' && $value !== '' && (!filter_var($value, FILTER_VALIDATE_URL)
            || !in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            throw new \InvalidArgumentException('Expected an HTTP(S) URL.');
        }
        return $value;
    }
}
