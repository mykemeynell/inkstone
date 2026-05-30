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
            <button class="inkstone-icon-button is-subtle inkstone-mobile-toggle" type="button" aria-label="Open navigation" data-inkstone-nav-toggle>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <a class="inkstone-brand" href="{{ $urls['home'] }}">
                @php
                    $logo = data_get($config, 'site.logo');
                    if (is_string($logo)) {
                        $logoLight = $logo;
                        $logoDark = $logo;
                    } elseif (is_array($logo)) {
                        $logoLight = $logo['light'] ?? null;
                        $logoDark = $logo['dark'] ?? $logoLight;
                    } else {
                        $logoLight = null;
                        $logoDark = null;
                    }
                @endphp
                @if($logoLight)
                    <img class="inkstone-brand-logo" src="{{ $logoLight }}" alt="" data-brand-logo="light">
                    <img class="inkstone-brand-logo" src="{{ $logoDark }}" alt="" data-brand-logo="dark" hidden>
                @endif
                @if(data_get($config, 'site.show_title'))
                    <span>{{ data_get($config, 'site.title', 'Inkstone') }}</span>
                @endif
            </a>
            <div class="inkstone-header-actions">
                @if($search['enabled'])
                    @include('inkstone::themes.default.partials.search-trigger', ['type' => $search['type']])
                @endif
                <button class="inkstone-icon-button is-subtle inkstone-theme-toggle" type="button" aria-label="Switch color theme" title="Switch color theme" data-inkstone-theme-toggle>
                    <span data-inkstone-theme-icon></span>
                </button>
            </div>
        </header>

        @php $expandedDefault = data_get($config, 'navigation.expanded', []); @endphp
        <aside class="inkstone-sidebar" data-inkstone-sidebar>
            <nav aria-label="Documentation">
                @foreach($navigation as $item)
                    @include('inkstone::themes.default.partials.navigation-item', ['item' => $item, 'expandedDefault' => $expandedDefault])
                @endforeach
            </nav>
        </aside>

        @php
            $findSection = function ($items) use (&$findSection) {
                foreach ($items as $item) {
                    if (! $item->active) continue;
                    if ($item->group !== null) return $item->group;
                    $childSection = $findSection($item->children);
                    if ($childSection !== null) return $childSection;
                }
                return null;
            };
            $sectionBadge = $findSection($navigation);
        @endphp
        <main class="inkstone-content">
            <article class="inkstone-prose">
                @if($sectionBadge)
                    <span class="inkstone-section-badge">{{ $sectionBadge }}</span>
                @endif
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

    @if($search['enabled'])
        <div class="inkstone-search-overlay" data-inkstone-search-overlay hidden>
            <div class="inkstone-search-dialog" role="dialog" aria-modal="true" aria-label="Search documentation">
                <div class="inkstone-search-row">
                    <label class="inkstone-search-control">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <span class="sr-only">Search docs</span>
                        <input class="inkstone-search" type="search" placeholder="Search docs" data-inkstone-search data-inkstone-search-index="{{ $urls['search_index'] }}" data-inkstone-search-driver="{{ $search['driver'] }}" data-inkstone-search-config="{{ json_encode($search['config']) }}">
                    </label>
                    <button class="inkstone-icon-button is-subtle" type="button" aria-label="Close search" title="Close search" data-inkstone-search-close>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="inkstone-search-results" data-inkstone-search-results hidden></div>
            </div>
        </div>
    @endif

    <button class="inkstone-back-to-top" type="button" aria-label="Back to top" title="Back to top" data-inkstone-back-to-top>
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 15 6-6 6 6"/></svg>
    </button>

    @foreach($search['scripts'] as $script)
        <script src="{{ $script }}"></script>
    @endforeach
    <script defer src="{{ $urls['script'] }}"></script>
</body>
</html>
