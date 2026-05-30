<?php

declare(strict_types=1);

namespace Inkstone\Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverKeys;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class StaticSiteBrowserTest extends TestCase
{
    private static string $baseUrl = 'http://127.0.0.1:8127';

    private static string $outputPath;

    private static ?Process $server = null;

    public static function setUpBeforeClass(): void
    {
        if (self::driverUrl() === null) {
            return;
        }

        self::$outputPath = dirname(__DIR__, 2).'/build/dusk-docs';

        $build = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/bin/inkstone',
            'docs:build',
            '--source=docs',
            '--output=build/dusk-docs',
        ], dirname(__DIR__, 2));

        $build->mustRun();

        self::$server = new Process([
            PHP_BINARY,
            '-S',
            '127.0.0.1:8127',
            '-t',
            self::$outputPath,
        ], dirname(__DIR__, 2));

        self::$server->start();
        usleep(300_000);
        Browser::$baseUrl = self::$baseUrl;
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();

        if (isset(self::$outputPath)) {
            (new Filesystem)->remove(self::$outputPath);
        }
    }

    public function test_search_results_are_static_and_keyboard_accessible(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $browser
                ->visit('/')
                ->waitFor('[data-inkstone-search-open]')
                ->click('[data-inkstone-search-open]')
                ->waitFor('[data-inkstone-search]')
                ->keys('[data-inkstone-search]', 'theme')
                ->waitFor('.inkstone-search-result')
                ->keys('[data-inkstone-search]', WebDriverKeys::ARROW_DOWN)
                ->assertScript("document.querySelector('.inkstone-search-result.is-active') !== null")
                ->keys('[data-inkstone-search]', WebDriverKeys::ENTER)
                ->waitForLocation('/features/themes');
        });
    }

    public function test_dark_mode_preference_persists_after_reload(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $browser
                ->visit('/')
                ->script("localStorage.setItem('inkstone-theme', 'dark'); window.location.reload();");

            $browser->waitFor('html.dark')
                ->assertScript("localStorage.getItem('inkstone-theme')", 'dark');
        });
    }

    public function test_mobile_navigation_scrolls_and_code_blocks_keep_stable_font_size(): void
    {
        $this->withBrowser(function (Browser $browser): void {
            $browser
                ->resize(390, 844)
                ->visit('/features/markdown')
                ->waitFor('[data-inkstone-nav-toggle]')
                ->click('[data-inkstone-nav-toggle]')
                ->assertScript("getComputedStyle(document.querySelector('[data-inkstone-sidebar]')).overflowY", 'auto')
                ->assertScript("document.querySelector('[data-inkstone-sidebar]').scrollHeight > document.querySelector('[data-inkstone-sidebar]').clientHeight")
                ->script("document.querySelector('[data-inkstone-sidebar]').scrollTo(0, 120);");

            $browser
                ->assertScript("document.querySelector('[data-inkstone-sidebar]').scrollTop > 0")
                ->assertScript("getComputedStyle(document.querySelector('pre')).fontSize === getComputedStyle(document.querySelector('pre code')).fontSize");
        });
    }

    /**
     * @param  callable(Browser): void  $callback
     */
    private function withBrowser(callable $callback): void
    {
        if (self::driverUrl() === null) {
            $this->markTestSkipped('Set DUSK_DRIVER_URL to run Inkstone browser tests.');
        }

        $driver = $this->driver();
        $browser = new Browser($driver);

        try {
            $callback($browser);
        } finally {
            $driver->quit();
        }
    }

    private function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--headless=new',
            '--disable-gpu',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--window-size=1280,720',
        ]);

        return RemoteWebDriver::create(
            self::driverUrl(),
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options),
        );
    }

    private static function driverUrl(): ?string
    {
        $url = $_SERVER['DUSK_DRIVER_URL'] ?? $_ENV['DUSK_DRIVER_URL'] ?? getenv('DUSK_DRIVER_URL') ?: null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
