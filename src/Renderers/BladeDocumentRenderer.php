<?php

declare(strict_types=1);

namespace Inkstone\Renderers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Inkstone\Contracts\DocumentRenderer;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\RenderedPage;
use Inkstone\Services\AssetManifest;
use Inkstone\Services\SearchDriverConfig;
use Inkstone\Services\ThemeResolver;
use Inkstone\Support\UrlBuilder;

final class BladeDocumentRenderer implements DocumentRenderer
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ThemeResolver $themeResolver,
        private readonly AssetManifest $assets,
        private readonly SearchDriverConfig $search,
    ) {}

    public function render(Document $document, array $documents, array $navigation, string $outputPath): RenderedPage
    {
        $config = (array) config('inkstone', []);
        $baseUrl = (string) data_get($config, 'site.base_url', '');
        $theme = (string) data_get($config, 'theme.name', 'default');
        $searchIndex = $this->search->indexPath();

        $searchDriver = (string) data_get($config, 'search.driver', 'json');
        $searchDriverConfig = (array) data_get($config, "search.drivers.{$searchDriver}.config", []);

        $driverScripts = (array) data_get($searchDriverConfig, 'scripts', []);
        $driverScripts[] = $this->assets->url(
            "resources/js/search-drivers/{$searchDriver}.js",
            'assets/js/search-driver.js',
            $baseUrl
        );

        $html = $this->view->make($this->themeResolver->pageView($config), [
            'document' => $document,
            'documents' => $documents,
            'navigation' => $navigation,
            'config' => $config,
            'urls' => [
                'home' => UrlBuilder::to($baseUrl),
                'stylesheet' => $this->assets->url('resources/css/inkstone.css', 'assets/css/inkstone.css', $baseUrl),
                'theme_stylesheet' => $this->assets->url('resources/css/themes/'.$theme.'.css', 'assets/css/themes/'.$theme.'.css', $baseUrl),
                'script' => $this->assets->url('resources/js/inkstone.js', 'assets/js/inkstone.js', $baseUrl),
                'search_index' => UrlBuilder::to($baseUrl, $searchIndex),
            ],
            'search' => [
                'enabled' => (bool) data_get($config, 'search.enabled', true),
                'type' => (string) data_get($config, 'search.type', 'button'),
                'driver' => $searchDriver,
                'config' => $searchDriverConfig,
                'scripts' => $driverScripts,
            ],
        ])->render();

        return new RenderedPage($document, $html, $outputPath);
    }
}
