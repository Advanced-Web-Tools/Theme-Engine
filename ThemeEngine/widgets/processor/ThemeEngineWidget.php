<?php

namespace ThemeEngine\widgets\processor;

use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCBlockRenderer;
use URPC\service\renderer\URPC;

class ThemeEngineWidget implements IURPCBlockRenderer
{

    public function process(IURPCBlock $block, URPC $urpc): void
    {
        $name = $block->getBlockDescriptor()->getBlockProps()['active_theme_name'] ?? 'No active theme';
        $this->populate($block, $name);
    }

    private function populate(IURPCBlock $block, string $name): void
    {
        if (($block->getBlockDescriptor()->getBlockProps()['theme_engine_role'] ?? null) === 'active_theme') {
            $block->setBlockContent($name);
        }

        foreach ($block->getBlockChildren() as $child) {
            $this->populate($child, $name);
        }
    }
}
