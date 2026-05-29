<?php

declare(strict_types=1);

namespace Inkstone\Renderers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Inkstone\Contracts\DocumentRenderer;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\RenderedPage;
use Inkstone\Services\ThemeResolver;
use Inkstone\Support\UrlBuilder;

final class BladeDocumentRenderer implements DocumentRenderer
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ThemeResolver $themeResolver,
    ) {}

    public function render(Document $document, array $documents, array $navigation, string $outputPath): RenderedPage
    {
        $config = (array) config('inkstone', []);
        $baseUrl = (string) data_get($config, 'site.base_url', '');
        $theme = (string) data_get($config, 'theme.name', 'default');
        $searchIndex = (string) data_get($config, 'search.index_path', 'search-index.json');

        $html = $this->view->make($this->themeResolver->pageView($config), [
            'document' => $document,
            'documents' => $documents,
            'navigation' => $navigation,
            'config' => $config,
            'urls' => [
                'home' => UrlBuilder::to($baseUrl),
                'stylesheet' => UrlBuilder::to($baseUrl, 'assets/css/inkstone.css'),
                'theme_stylesheet' => UrlBuilder::to($baseUrl, 'assets/css/themes/'.$theme.'.css'),
                'script' => UrlBuilder::to($baseUrl, 'assets/js/inkstone.js'),
                'search_index' => UrlBuilder::to($baseUrl, $searchIndex),
            ],
        ])->render();

        return new RenderedPage($document, $html, $outputPath);
    }
}
