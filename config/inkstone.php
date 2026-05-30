<?php

declare(strict_types=1);

use Inkstone\Search\AlgoliaSearchIndexer;
use Inkstone\Search\JsonSearchIndexer;
use Inkstone\Search\LunrSearchIndexer;
use Inkstone\Search\TypesenseSearchIndexer;
use Inkstone\Transformers\DemoBlockTransformer;
use Inkstone\Transformers\ExternalLinkTransformer;
use Inkstone\Transformers\GitHubRelativeLinkTransformer;
use Inkstone\Transformers\HeadingAnchorTransformer;
use Inkstone\Transformers\SyntaxHighlightTransformer;
use Phiki\Theme\Theme;

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation Source & Output
    |--------------------------------------------------------------------------
    |
    | The source_path is the directory containing your Markdown documentation.
    | The output_path is where the generated static site will be written.
    |
    */

    'source_path' => base_path('docs'),

    'output_path' => base_path('build/docs'),

    /*
    |--------------------------------------------------------------------------
    | Site Metadata
    |--------------------------------------------------------------------------
    |
    | Global site configuration used for SEO and UI elements.
    |
    */

    'site' => [

        'title' => env('INKSTONE_TITLE', 'Inkstone Docs'),

        'description' => env(
            'INKSTONE_DESCRIPTION',
            'Project documentation generated with Inkstone.'
        ),

        'base_url' => env('INKSTONE_BASE_URL', ''),

        'favicon' => null,

        'logo' => [
            'light' => null,
            'dark' => null,
        ],

        'show_title' => true,

        'footer' => [
            'enabled' => true,
            'text' => 'Built with Inkstone',
            'url' => 'https://github.com/mykemeynell/inkstone',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Theme & Styling
    |--------------------------------------------------------------------------
    |
    | Configure the look and feel of your documentation site.
    |
    */

    'theme' => [

        'name' => 'default',

        'layout' => 'default',

        'default_mode' => 'system',

        'syntax_highlighting' => [
            'enabled' => true,
            'theme' => [
                'light' => Theme::GithubLight,
                'dark' => Theme::GithubDark,
            ],
            'show_line_numbers' => true,
            'copy_button' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown & Navigation
    |--------------------------------------------------------------------------
    */

    'markdown' => [

        'unsafe_links' => false,

        'html_input' => 'allow',

        'renderer' => [
            'soft_break' => "\n",
        ],

    ],

    'navigation' => [

        'expanded' => [],

    ],

    'discovery' => [

        'ignore' => [
            'vendor',
            'node_modules',
            'build',
            'storage',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    'search' => [

        'enabled' => true,

        'driver' => env('INKSTONE_SEARCH_DRIVER', 'json'),

        'max_content_length' => 5000,

        'type' => 'input',

        'drivers' => [

            'json' => [
                'driver' => JsonSearchIndexer::class,
                'config' => [
                    'index_path' => 'search-index.json',
                ],
            ],

            'lunr' => [
                'driver' => LunrSearchIndexer::class,
                'config' => [
                    'index_path' => 'lunr-index.json',
                    'scripts' => [
                        'https://unpkg.com/lunr/lunr.js',
                    ],
                ],
            ],

            'algolia' => [
                'driver' => AlgoliaSearchIndexer::class,
                'config' => [
                    'app_id' => env('INKSTONE_ALGOLIA_APP_ID'),
                    'api_key' => env('INKSTONE_ALGOLIA_SEARCH_KEY'),
                    'index_name' => env('INKSTONE_ALGOLIA_INDEX_NAME'),
                    'scripts' => [
                        'https://cdn.jsdelivr.net/npm/algoliasearch@4/dist/algoliasearch-lite.umd.js',
                    ],
                ],
            ],

            'typesense' => [
                'driver' => TypesenseSearchIndexer::class,
                'config' => [
                    'server' => [
                        'nodes' => [
                            [
                                'host' => env('INKSTONE_TYPESENSE_HOST', 'localhost'),
                                'port' => env('INKSTONE_TYPESENSE_PORT', '8108'),
                                'protocol' => env('INKSTONE_TYPESENSE_PROTOCOL', 'http'),
                            ],
                        ],
                        'api_key' => env('INKSTONE_TYPESENSE_SEARCH_KEY'),
                        'collection_name' => env('INKSTONE_TYPESENSE_COLLECTION_NAME'),
                    ],
                    'scripts' => [
                        'https://cdn.jsdelivr.net/npm/typesense@1/dist/typesense.umd.js',
                    ],
                ],
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    */

    'github' => [

        'repository' => env(
            'INKSTONE_GITHUB_REPOSITORY',
            'https://github.com/vendor/package'
        ),

        'branch' => env('INKSTONE_GITHUB_BRANCH', 'main'),

        'rewrite_relative_links' => true,

        'rewrite_images' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Build & Assets
    |--------------------------------------------------------------------------
    */

    'build' => [

        'clean_output_before_build' => true,

        'pretty_urls' => true,

        'generate_sitemap' => true,

        'generate_robots_txt' => true,

        'asset_hashing' => true,

        'assets' => [
            'additional_paths' => [
                resource_path('docs-assets'),
            ],
            'dist_path' => null,
            'manifest_path' => null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Blocks
    |--------------------------------------------------------------------------
    |
    | Configuration for executable demo fences.
    |
    */

    'demos' => [

        'enabled' => true,

        'execute_php' => true,

        'execute_blade' => true,

        'describe_void_output' => false,

        'use_disposable_database' => false,

        'database' => [
            'connection' => 'inkstone_demo',
            'database' => ':memory:',
        ],

        'show_stack_traces' => false,

        'sandbox' => [
            'enabled' => true,
            'timeout' => 5,
            'memory_limit' => '128M',
            'allow_filesystem_writes' => false,
            'allow_process_execution' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Local Development Server
    |--------------------------------------------------------------------------
    */

    'server' => [

        'host' => '127.0.0.1',

        'port' => 8080,

    ],

    /*
    |--------------------------------------------------------------------------
    | Transformers
    |--------------------------------------------------------------------------
    */

    'transformers' => [
        HeadingAnchorTransformer::class,
        ExternalLinkTransformer::class,
        GitHubRelativeLinkTransformer::class,
        DemoBlockTransformer::class,
        SyntaxHighlightTransformer::class,
    ],

];
