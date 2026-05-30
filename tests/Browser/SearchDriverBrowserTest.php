<?php

declare(strict_types=1);

namespace Inkstone\Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class SearchDriverBrowserTest extends TestCase
{
    private static string $baseUrl = 'http://127.0.0.1:8128';

    private static string $outputPath;

    private static ?Process $server = null;

    protected function tearDown(): void
    {
        self::$server?->stop();

        if (isset(self::$outputPath)) {
            (new Filesystem)->remove(self::$outputPath);
        }

        parent::tearDown();
    }

    private function buildAndServe(string $driver, array $config = []): void
    {
        self::$outputPath = dirname(__DIR__, 2).'/build/dusk-search-'.$driver;

        config()->set('inkstone.search.driver', $driver);
        foreach ($config as $key => $value) {
            config()->set("inkstone.search.drivers.{$driver}.config.{$key}", $value);
        }
        config()->set('inkstone.output_path', 'build/dusk-search-'.$driver);

        $build = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/bin/inkstone',
            'docs:build',
            '--source=tests/fixtures',
            '--output=build/dusk-search-'.$driver,
        ], dirname(__DIR__, 2));

        $build->mustRun();

        self::$server = new Process([
            PHP_BINARY,
            '-S',
            '127.0.0.1:8128',
            '-t',
            self::$outputPath,
        ], dirname(__DIR__, 2));

        self::$server->start();
        usleep(300_000);
        Browser::$baseUrl = self::$baseUrl;
    }

    public function test_json_search_driver_works(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $this->buildAndServe('json');

            $browser->visit('/')
                ->waitFor('[data-inkstone-search-open]')
                ->click('[data-inkstone-search-open]')
                ->waitFor('[data-inkstone-search]')
                ->keys('[data-inkstone-search]', 'markdown')
                ->waitFor('.inkstone-search-result')
                ->assertSeeIn('.inkstone-search-result', 'Markdown');
        });
    }

    public function test_lunr_search_driver_works(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $this->buildAndServe('lunr');

            $browser->visit('/')
                ->waitFor('[data-inkstone-search-open]')
                ->click('[data-inkstone-search-open]')
                ->waitFor('[data-inkstone-search]')
                ->keys('[data-inkstone-search]', 'markdown')
                ->waitFor('.inkstone-search-result')
                ->assertSeeIn('.inkstone-search-result', 'Markdown');
        });
    }

    public function test_algolia_search_driver_loads_scripts_and_config(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $this->buildAndServe('algolia', [
                'app_id' => 'ALGOLIA_APP',
                'api_key' => 'ALGOLIA_KEY',
                'index_name' => 'ALGOLIA_INDEX',
            ]);

            $browser->visit('/')
                ->assertSourceHas('algoliasearch-lite.umd.js')
                ->assertAttribute('[data-inkstone-search]', 'data-inkstone-search-driver', 'algolia')
                ->assertScript("JSON.parse(document.querySelector('[data-inkstone-search]').dataset.inkstoneSearchConfig).app_id", 'ALGOLIA_APP');
        });
    }

    public function test_typesense_search_driver_loads_scripts_and_config(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $this->buildAndServe('typesense', [
                'server' => [
                    'nodes' => [['host' => 'typesense.test', 'port' => '8108', 'protocol' => 'https']],
                    'api_key' => 'TYPESENSE_KEY',
                    'collection_name' => 'docs',
                ],
            ]);

            $browser->visit('/')
                ->assertSourceHas('typesense.umd.js')
                ->assertAttribute('[data-inkstone-search]', 'data-inkstone-search-driver', 'typesense')
                ->assertScript("JSON.parse(document.querySelector('[data-inkstone-search]').dataset.inkstoneSearchConfig).server.api_key", 'TYPESENSE_KEY');
        });
    }

    private function withBrowser(callable $callback): void
    {
        $url = $this->driverUrl();
        if ($url === null) {
            $this->markTestSkipped('Set DUSK_DRIVER_URL to run Inkstone browser tests.');
        }

        $options = (new ChromeOptions)->addArguments([
            '--headless=new',
            '--disable-gpu',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--window-size=1280,720',
        ]);

        $driver = RemoteWebDriver::create(
            $url,
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options),
        );

        $browser = new Browser($driver);

        try {
            $callback($browser);
        } finally {
            $driver->quit();
        }
    }

    private function driverUrl(): ?string
    {
        $url = $_SERVER['DUSK_DRIVER_URL'] ?? $_ENV['DUSK_DRIVER_URL'] ?? getenv('DUSK_DRIVER_URL') ?: null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
