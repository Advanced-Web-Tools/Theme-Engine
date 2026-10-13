<?php

use database\creator\ColumnCreator as C;
use database\creator\TableWizard;
use database\provider\DatabaseProvider;
use installer\interfaces\package\actions\IPostInstall;
use ThemeEngine\service\Engine;

final class ThemeEngineInstall implements IPostInstall
{
    public function postInstall(int $packageId, string $packageName): bool
    {
        $tables = [
            'theme_engine_state' => [C::INT('id')->primary(), C::LONGTEXT('payload')],
            'theme_engine_settings' => [C::INT('package_id')->primary(), C::LONGTEXT('payload')],
            'theme_engine_presets' => [
                C::INT('id')->primary()->autoIncrement(),
                C::INT('package_id')->index(),
                C::VARCHAR('name', '120'),
                C::LONGTEXT('payload'),
            ],
        ];
        foreach ($tables as $name => $columns) {
            $wizard = TableWizard::create($packageId);
            if ($wizard->tableExists($name)) {
                continue;
            }
            foreach ($columns as $column) {
                $wizard->addColumn($column);
            }
            if (!$wizard->createTable($name)) {
                return false;
            }
        }
        (new DatabaseProvider())->execute(
            'INSERT IGNORE INTO theme_engine_state (id, payload) VALUES (1, :payload)',
            [':payload' => json_encode(Engine::defaultState(), JSON_THROW_ON_ERROR)],
        )->closeCursor();
        return true;
    }
}
