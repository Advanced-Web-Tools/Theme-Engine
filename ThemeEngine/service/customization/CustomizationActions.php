<?php

namespace ThemeEngine\service\customization;

use ThemeEngine\service\Engine;
use ThemeEngine\service\SidebarWidgetSettings;

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
            case 'widgets':
                $engine->saveGlobalWidgets($input->array('widgets'));
                break;
            case 'page_widgets':
                $placements = $input->array('widgets');
                $modes = $input->array('widget_mode');
                if (array_diff_key($modes, SidebarWidgetSettings::SIDEBARS)) {
                    throw new \InvalidArgumentException('Unknown sidebar.');
                }
                foreach (SidebarWidgetSettings::SIDEBARS as $sidebar => $label) {
                    $mode = $modes[$sidebar] ?? 'inherit';
                    if (!in_array($mode, ['inherit', 'custom'], true)) {
                        throw new \InvalidArgumentException('Invalid widget scope.');
                    }
                    if ($mode === 'inherit') unset($placements[$sidebar]);
                    else $placements[$sidebar] ??= [];
                }
                $engine->savePageWidgets($input->string('page_key'), $placements);
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
