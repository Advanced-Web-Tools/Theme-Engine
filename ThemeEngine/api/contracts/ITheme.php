<?php

namespace ThemeEngine\api\contracts;

use ThemeEngine\api\ThemeDefinition;

interface ITheme
{
    public function getDefinition(): ThemeDefinition;
}
