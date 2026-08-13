<?php

declare(strict_types=1);

namespace Inkstone\Extensions;

use Illuminate\Contracts\Container\Container;
use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;
use RuntimeException;

final class BuildExtensionPipeline
{
    public function __construct(private readonly Container $container) {}

    public function run(BuildContext $context): void
    {
        foreach ($this->configuredExtensions() as $extensionClass) {
            $extension = $this->container->make($extensionClass);

            if (! $extension instanceof BuildExtension) {
                throw new RuntimeException(
                    sprintf('Configured Inkstone extension [%s] must implement %s.', $extensionClass, BuildExtension::class),
                );
            }

            $extension->afterBuild($context);
        }
    }

    /**
     * @return list<class-string>
     */
    private function configuredExtensions(): array
    {
        $config = $this->container->make('config');
        $configured = $config->has('inkstone.extensions')
            ? $config->get('inkstone.extensions')
            : [SitemapExtension::class];

        if (! is_array($configured)) {
            throw new RuntimeException('The Inkstone extensions configuration must be an array of class names.');
        }

        $extensions = [];

        foreach ($configured as $extensionClass) {
            if (! is_string($extensionClass) || trim($extensionClass) === '') {
                throw new RuntimeException('Each configured Inkstone extension must be a non-empty class name.');
            }

            $extensions[] = $extensionClass;
        }

        return array_values(array_unique($extensions));
    }
}
