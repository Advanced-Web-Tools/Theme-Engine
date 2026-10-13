<?php

namespace ThemeEngine\api\contracts;

use ThemeEngine\api\ThemePageContext;
use URPC\api\contracts\IURPCBlock;

interface ISectionProvider
{
    /** @return list<IURPCBlock> Fresh blocks on every call. */
    public function createBlocks(ThemePageContext $context): array;
}
