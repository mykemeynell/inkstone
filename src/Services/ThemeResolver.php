<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class ThemeResolver
{
    public function __construct(private readonly ViewFactory $view) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function pageView(array $config): string
    {
        $layout = trim((string) data_get($config, 'theme.layout', 'default'));
        $layout = $layout !== '' ? $layout : 'default';
        $view = "inkstone::themes.{$layout}.page";

        return $this->view->exists($view) ? $view : 'inkstone::themes.default.page';
    }
}
