<?php

declare(strict_types=1);
use Inkstone\Transformers\DemoBlockTransformer;
use Inkstone\Transformers\ExternalLinkTransformer;
use Inkstone\Transformers\GitHubRelativeLinkTransformer;
use Inkstone\Transformers\HeadingAnchorTransformer;
use Inkstone\Transformers\SyntaxHighlightTransformer;
use Phiki\Theme\Theme;

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation Source Path
    |--------------------------------------------------------------------------
    |
    | The directory containing your Markdown documentation files.
    |
    */

    'docs_path' => base_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | Build Output Path
    |--------------------------------------------------------------------------
    |
    | The generated static documentation site will be written here.
    |
    */

    'output_path' => base_path('build/docs'),

    /*
    |--------------------------------------------------------------------------
    | Site Metadata
    |--------------------------------------------------------------------------
    |
    | Global site configuration.
    |
    */

    'site' => [

        'title' => env('DOCS_TITLE', 'Inkstone Documentation'),

        'description' => env(
            'DOCS_DESCRIPTION',
            'Project documentation generated with Inkstone.'
        ),

        'base_url' => env('DOCS_BASE_URL', ''),

        'favicon' => null,

        'logo' => null,

        'footer' => [
            'enabled' => true,
            'text' => 'Built with Inkstone',
            'url' => 'https://github.com/mykemeynell/inkstone',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Configuration
    |--------------------------------------------------------------------------
    */

    'theme' => [

        'name' => 'default',

        'layout' => 'default',

        'available' => [
            'default',
            'light',
            'dark',
            'ember',
            'forest',
        ],

        'dark_mode' => true,

        'default_mode' => 'system',

        'sidebar_collapsible' => true,

        'max_content_width' => '4xl',

        'show_table_of_contents' => true,

        'show_breadcrumbs' => true,

        'code_block_theme' => [
            'light' => Theme::GithubLight,
            'dark' => Theme::GithubDark,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Configuration
    |--------------------------------------------------------------------------
    */

    'markdown' => [

        'unsafe_links' => false,

        'html_input' => 'allow',

        'renderer' => [

            'soft_break' => "\n",

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    'navigation' => [

        'auto_generate' => true,

        'sort' => 'frontmatter',

        'fallback_sort' => 'alphabetical',

        'max_depth' => 10,

    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery
    |--------------------------------------------------------------------------
    */

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

        'driver' => 'fuse',

        'index_path' => 'search-index.json',

        'include_headings' => true,

        'include_content' => true,

        'max_content_length' => 5000,

    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    */

    'github' => [

        /*
        |--------------------------------------------------------------------------
        | Repository URL
        |--------------------------------------------------------------------------
        |
        | Examples:
        |
        | https://github.com/vendor/package
        |
        */

        'repository' => env(
            'DOCS_GITHUB_REPOSITORY',
            'https://github.com/vendor/package'
        ),

        /*
        |--------------------------------------------------------------------------
        | Branch / Tag
        |--------------------------------------------------------------------------
        */

        'branch' => env('DOCS_GITHUB_BRANCH', 'main'),

        /*
        |--------------------------------------------------------------------------
        | Rewrite Relative Links
        |--------------------------------------------------------------------------
        */

        'rewrite_relative_links' => true,

        /*
        |--------------------------------------------------------------------------
        | Rewrite Image Sources
        |--------------------------------------------------------------------------
        */

        'rewrite_images' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Static Site Generation
    |--------------------------------------------------------------------------
    */

    'build' => [

        'clean_output_before_build' => true,

        'pretty_urls' => true,

        'generate_sitemap' => true,

        'generate_robots_txt' => true,

        'minify_html' => false,

        'asset_hashing' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Syntax Highlighting
    |--------------------------------------------------------------------------
    */

    'syntax_highlighting' => [

        'enabled' => true,

        'driver' => 'phiki',

        'theme_light' => 'github-light',

        'theme_dark' => 'github-dark',

        'show_line_numbers' => true,

        'copy_button' => true,

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

        /*
        |--------------------------------------------------------------------------
        | Runtime Execution
        |--------------------------------------------------------------------------
        */

        'execute_php' => true,

        'execute_blade' => true,

        'execute_livewire' => true,

        /*
        |--------------------------------------------------------------------------
        | Output Handling
        |--------------------------------------------------------------------------
        */

        'render_renderables' => true,

        'dump_non_renderables' => true,

        'pretty_print_arrays' => true,

        'pretty_print_objects' => true,

        'describe_void_output' => false,

        'use_disposable_database' => false,

        /*
        |--------------------------------------------------------------------------
        | Error Handling
        |--------------------------------------------------------------------------
        */

        'show_exceptions' => true,

        'show_stack_traces' => false,

        /*
        |--------------------------------------------------------------------------
        | Sandboxing
        |--------------------------------------------------------------------------
        */

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
    | Assets
    |--------------------------------------------------------------------------
    */

    'assets' => [

        'copy_images' => true,

        'copy_static_assets' => true,

        'additional_paths' => [

            resource_path('docs-assets'),

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

        'watch' => true,

        'open_browser' => true,

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

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [

        'enabled' => true,

        'store' => env('DOCS_CACHE_STORE', 'file'),

        'ttl' => 3600,

    ],

    /*
    |--------------------------------------------------------------------------
    | Experimental Features
    |--------------------------------------------------------------------------
    */

    'experimental' => [

        'versioned_docs' => false,

        'component_playgrounds' => false,

        'ai_search' => false,

    ],

];
