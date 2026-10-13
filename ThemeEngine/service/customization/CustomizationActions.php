<?php

namespace ThemeEngine\service\customization;

use ThemeEngine\service\Engine;

/** Executes a validated dashboard operation against the already selected theme. */
final class CustomizationActions
{
    public function apply(Engine $engine, CustomizationInput $input): void
    {
        switch ($input->operation()) {
            case 'activate':
                $engine->activate($input->positiveId('package_id'));
                break;
            case 'settings':
                $engine->saveSettings($input->array('settings'));
                break;
            case 'reset_settings':
                $engine->resetSettings();
                break;
            case 'layout':
                $engine->saveGlobalSections($input->array('sections'));
                break;
            case 'page':
                $engine->savePageSections($input->string('page_key'), $input->array('sections'));
                break;
            case 'preset_save':
                $engine->savePreset($input->string('preset_name'));
                break;
            case 'preset_apply':
                $engine->applyPreset($input->positiveId('preset_id'));
                break;
            case 'preset_delete':
                $engine->deletePreset($input->positiveId('preset_id'));
                break;
            case 'cache_clear':
                $engine->cache->clear();
                break;
            default:
                throw new \InvalidArgumentException('Unknown customization operation.');
        }
    }
}
