<?php

namespace ThemeEngine\service\contracts;

/** Persistence boundary shared by production storage and in-memory test fixtures. */
interface IStore
{
    public function state(): array;

    public function saveState(array $state): void;

    public function config(int $packageId): array;

    public function saveConfig(int $packageId, array $config): void;

    public function presets(int $packageId): array;

    public function savePreset(int $packageId, string $name, array $payload): int;

    public function deletePreset(int $packageId, int $id): void;

    public function transaction(callable $callback): mixed;
}
