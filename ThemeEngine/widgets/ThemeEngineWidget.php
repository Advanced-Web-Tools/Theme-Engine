<?php

namespace ThemeEngine\widgets;

use Dashboard\api\widget\DashboardWidget;
use ThemeEngine\service\Engine;
use ThemeEngine\widgets\processor\ThemeEngineWidget as ThemeEngineProcessor;
use URPC\api\URPCBlock;
use URPC\api\URPCBlockDescriptor;

class ThemeEngineWidget extends DashboardWidget
{
    public function __construct(private readonly Engine $engine)
    {
        parent::__construct();
    }

    public function createChildren(): array
    {
        $hasActiveTheme = $this->engine->hasActiveTheme();

        $title = new URPCBlock(new URPCBlockDescriptor("h2"));
        $title->setBlockContent("Your appearance");
        $title->setBlockAttributes(["class" => "card-title"]);

        $description = new URPCBlock(new URPCBlockDescriptor("p"));
        $description->setBlockContent($hasActiveTheme
            ? "Personalize your site's appearance."
            : "Choose a theme to set the look of your site.");
        $description->setBlockAttributes(["class" => "card-subtitle text-small"]);

        $header = new URPCBlock(new URPCBlockDescriptor("header"));
        $header->setBlockChildren([$title, $description]);

        $status = new URPCBlock(new URPCBlockDescriptor("span"));
        $status->setBlockContent($hasActiveTheme ? "Active theme" : "No active theme");
        $status->setBlockAttributes(["class" => $hasActiveTheme ? "badge badge-success" : "badge badge-muted"]);

        $theme = new URPCBlock(new URPCBlockDescriptor("strong", ["theme_engine_role" => "active_theme"]));
        $theme->setBlockAttributes(["class" => "min-w-0"]);

        $summary = new URPCBlock(new URPCBlockDescriptor("div"));
        $summary->setBlockAttributes(["class" => "d-flex flex-wrap align-items-center gap-2 mt-3"]);
        $summary->setBlockChildren([$status, $theme]);

        $customize = new URPCBlock(new URPCBlockDescriptor("a"));
        $customize->setBlockContent($hasActiveTheme ? "Customize theme" : "Choose a theme");
        $customize->setBlockAttributes([
            "class" => "btn btn-primary",
            "href" => "/dashboard/customization?tab=" . ($hasActiveTheme ? "settings" : "themes"),
        ]);

        $actions = [$customize];
        if ($hasActiveTheme) {
            $changeTheme = new URPCBlock(new URPCBlockDescriptor("a"));
            $changeTheme->setBlockContent("Change theme");
            $changeTheme->setBlockAttributes([
                "class" => "btn btn-outline",
                "href" => "/dashboard/customization?tab=themes",
            ]);
            $actions[] = $changeTheme;
        }

        $footer = new URPCBlock(new URPCBlockDescriptor("footer"));
        $footer->setBlockAttributes([
            "class" => "d-flex flex-wrap align-items-center gap-1 mt-4",
        ]);
        $footer->setBlockChildren($actions);

        return [$header, $summary, $footer];
    }

    public function widget(): DashboardWidget
    {
        $descriptor = new URPCBlockDescriptor("section", [
            "active_theme_name" => $this->engine->hasActiveTheme()
                ? $this->engine->activeTheme()->name
                : "Choose your first theme",
        ]);
        $descriptor->setSourceKey("ThemeEngine/widgets/appearance");
        $descriptor->setBlockRenderer(ThemeEngineProcessor::class);

        $this->setBlockDescriptor($descriptor);
        $this->setSize(self::SIZE_SMALL);

        $this->setBlockAttributes(array_replace($this->getBlockAttributes(), ["class" => "card min-w-0"]));

        $this->setBlockChildren($this->createChildren());

        return $this;
    }
}
