<!doctype html>
<html lang="en" class="inkstone" data-theme="{{ data_get($config, 'theme.default_mode', 'system') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title() }} - {{ data_get($config, 'site.title', 'Inkstone Documentation') }}</title>
    <meta name="description" content="{{ data_get($config, 'site.description', '') }}">
    @if(data_get($config, 'site.favicon'))
        <link rel="icon" href="{{ data_get($config, 'site.favicon') }}">
    @endif
    <link rel="stylesheet" href="{{ $urls['stylesheet'] }}">
    <link rel="stylesheet" href="{{ $urls['theme_stylesheet'] }}">
    <script>
        (() => {
            const stored = localStorage.getItem('inkstone-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const mode = stored || document.documentElement.dataset.theme || 'system';
            document.documentElement.dataset.theme = mode;
            document.documentElement.classList.toggle('dark', mode === 'dark' || (mode === 'system' && prefersDark));
        })();
    </script>
</head>
<body>
    <div class="inkstone-shell">
        <header class="inkstone-header">
            <button class="inkstone-icon-button inkstone-mobile-toggle" type="button" aria-label="Open navigation" data-inkstone-nav-toggle>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <a class="inkstone-brand" href="{{ $urls['home'] }}">
                @if(data_get($config, 'site.logo'))
                    <img src="{{ data_get($config, 'site.logo') }}" alt="">
                @endif
                <span>{{ data_get($config, 'site.title', 'Inkstone') }}</span>
            </a>
            <div class="inkstone-header-actions">
                @if(data_get($config, 'search.enabled', true))
                    <label class="inkstone-search-control">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <span class="sr-only">Search docs</span>
                        <input class="inkstone-search" type="search" placeholder="Search docs" data-inkstone-search data-inkstone-search-index="{{ $urls['search_index'] }}">
                    </label>
                @endif
                <button class="inkstone-icon-button inkstone-theme-toggle" type="button" aria-label="Switch color theme" title="Switch color theme" data-inkstone-theme-toggle>
                    <span data-inkstone-theme-icon></span>
                </button>
            </div>
        </header>

        <aside class="inkstone-sidebar" data-inkstone-sidebar>
            <nav aria-label="Documentation">
                @foreach($navigation as $item)
                    @include('inkstone::themes.default.partials.navigation-item', ['item' => $item])
                @endforeach
            </nav>
        </aside>

        <main class="inkstone-content">
            <article class="inkstone-prose">
                {!! $document->html !!}
            </article>

            @if(data_get($config, 'site.footer.enabled', true))
                <footer class="inkstone-footer">
                    @if(data_get($config, 'site.footer.url'))
                        <a href="{{ data_get($config, 'site.footer.url') }}" target="_blank" rel="noopener noreferrer">{{ data_get($config, 'site.footer.text', 'Built with Inkstone') }}</a>
                    @else
                        <span>{{ data_get($config, 'site.footer.text', 'Built with Inkstone') }}</span>
                    @endif
                </footer>
            @endif
        </main>

        <aside class="inkstone-toc">
            @if($document->headings !== [])
                <nav aria-label="On this page">
                    <p>On this page</p>
                    @foreach($document->headings as $heading)
                        @if($heading->level > 1)
                            <a style="--depth: {{ $heading->level - 2 }}" href="#{{ $heading->id }}" data-inkstone-toc-link="{{ $heading->id }}">{{ $heading->text }}</a>
                        @endif
                    @endforeach
                </nav>
            @endif
        </aside>
    </div>

    @if(data_get($config, 'search.enabled', true))
        <div class="inkstone-search-results" data-inkstone-search-results hidden></div>
    @endif

    <button class="inkstone-back-to-top" type="button" aria-label="Back to top" title="Back to top" data-inkstone-back-to-top>
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 15 6-6 6 6"/></svg>
    </button>

    <script defer src="{{ $urls['script'] }}"></script>
</body>
</html>
