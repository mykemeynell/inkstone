<?php

declare(strict_types=1);

namespace Inkstone\Tests;

use Inkstone\Facades\DocsGenerator;
use Inkstone\Providers\InkstoneServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\Filesystem\Filesystem;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupPublishedInkstoneTestbenchArtifacts();
    }

    protected function tearDown(): void
    {
        $this->cleanupPublishedInkstoneTestbenchArtifacts();
        Mockery::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            InkstoneServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return ['DocsGenerator' => DocsGenerator::class];
    }

    protected function defineEnvironment($app): void
    {
        $config = require __DIR__.'/../config/inkstone.php';

        $app['config']->set('inkstone', $config);
        $app['config']->set('app.env', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function cleanupPublishedInkstoneTestbenchArtifacts(): void
    {
        if (! function_exists('resource_path')) {
            return;
        }

        (new Filesystem)->remove([
            resource_path('views/vendor/inkstone'),
            resource_path('inkstone'),
        ]);
    }
}
