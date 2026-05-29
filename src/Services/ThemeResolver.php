<?php

declare(strict_types=1);

namespace Inkstone\Services;

final class ThemeResolver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function pageView(array $config): string
    {
        $theme = (string) data_get($config, 'theme.name', 'default');

        return "inkstone::themes.{$theme}.page";
    }
}
