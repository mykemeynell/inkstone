<?php

declare(strict_types=1);

namespace Inkstone\Demos;

final class DemoRuntimeResolver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = []) {}

    public function resolve(string $language): string
    {
        $language = strtolower($language);

        if (in_array($language, ['markdown', 'md'], true)) {
            return 'markdown';
        }

        if ($language === 'html') {
            return 'html';
        }

        if ($language === 'blade') {
            return (bool) data_get($this->config, 'execute_blade', true) ? 'blade' : 'static';
        }

        if (in_array($language, ['php', 'eloquent', 'collection', 'collections', 'container', 'service-container'], true)) {
            return (bool) data_get($this->config, 'execute_php', true) ? 'php' : 'static';
        }

        if (in_array($language, ['js', 'javascript', 'jsx', 'tsx', 'vue', 'react', 'livewire'], true)) {
            return 'static';
        }

        return 'static';
    }
}
