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

final class SearchPrecisionBrowserTest extends TestCase
{
    private static string $baseUrl = 'http://127.0.0.1:8129';

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

    private function buildAndServe(): void
    {
        self::$outputPath = dirname(__DIR__, 2).'/build/dusk-search-precision';

        $build = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/bin/inkstone',
            'docs:build',
            '--source=tests/fixtures',
            '--output=build/dusk-search-precision',
        ], dirname(__DIR__, 2));

        $build->mustRun();

        self::$server = new Process([
            PHP_BINARY,
            '-S',
            '127.0.0.1:8129',
            '-t',
            self::$outputPath,
        ], dirname(__DIR__, 2));

        self::$server->start();
        usleep(300_000);
        Browser::$baseUrl = self::$baseUrl;
    }

    public function test_it_shows_search_term_in_context_when_it_appears_deep_in_the_content(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $this->buildAndServe();

            $browser->visit('/')
                ->waitFor('[data-inkstone-search-open]')
                ->click('[data-inkstone-search-open]')
                ->waitFor('[data-inkstone-search]')
                ->keys('[data-inkstone-search]', 'targetword')
                ->waitFor('.inkstone-search-result')
                ->assertSeeIn('.inkstone-search-result', 'The secret word is')
                ->assertPresent('.inkstone-search-result mark');
        });
    }

    public function test_logo_svg_search_highlights_matching_term(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $this->buildAndServe();

            $browser->visit('/')
                ->waitFor('[data-inkstone-search-open]')
                ->click('[data-inkstone-search-open]')
                ->waitFor('[data-inkstone-search]')
                ->keys('[data-inkstone-search]', 'logo.svg')
                ->waitFor('.inkstone-search-result')
                ->assertPresent('.inkstone-search-result mark');
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
