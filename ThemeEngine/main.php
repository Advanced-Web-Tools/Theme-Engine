<?php

namespace ThemeEngine;

use Dashboard\api\events\NavigationResponseEvent;
use Dashboard\api\navigation\NavigationItem;
use runtime\api\RuntimeLinkerAPI;

final class ThemeEngineMain extends RuntimeLinkerAPI
{
    public function environmentSetup(): void
    {
        parent::environmentSetup();
        $this->waitForPackage('URPC');
    }

    public function main(): void
    {
        $this->createLink('loader', 'runtime/ThemeLoaderRuntime.php');
        $this->createLink('controllers', 'runtime/ControllerRuntime.php');
        $this->createLink('dashboard', 'runtime/DashboardRouterRuntime.php');
        $this->createLink('public', 'runtime/PublicRouterRuntime.php');

        $navigation = new NavigationItem();
        $navigation->setUrl('/dashboard/customization');
        $navigation->setLabel('Customization');
        $navigation->setIcon('/awt_packages/ThemeEngine/data/icons/icon.webp');
        $navigation->setPermissionLevel(1);
        $this->eventDispatcher->addListener(NavigationResponseEvent::NAME, new NavigationResponseEvent($navigation));
    }
}
