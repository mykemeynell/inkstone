@php
    $shouldExpand = $item->active
        || (is_bool($expandedDefault) && $expandedDefault)
        || (is_array($expandedDefault) && in_array($item->slug, $expandedDefault, true));
    $initialCollapsed = $shouldExpand ? 'false' : 'true';
    $initialExpanded = $shouldExpand ? 'true' : 'false';
@endphp
@if($item->children !== [])
    <span class="inkstone-nav-item inkstone-nav-parent {{ $item->active ? 'is-active' : '' }}"
          role="button"
          tabindex="0"
          aria-expanded="{{ $initialExpanded }}"
          data-collapsed="{{ $initialCollapsed }}"
          data-slug="{{ $item->slug }}">
        {{ $item->title }}
        <svg class="inkstone-nav-chevron" viewBox="0 0 24 24" aria-hidden="true" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
    </span>
@else
    <a class="inkstone-nav-item {{ $item->active ? 'is-active' : '' }}" href="{{ $item->url }}">{{ $item->title }}</a>
@endif
@if($item->children !== [])
    <div class="inkstone-nav-children" data-collapsed="{{ $initialCollapsed }}">
        @foreach($item->children as $child)
            @include('inkstone::themes.default.partials.navigation-item', ['item' => $child, 'expandedDefault' => $expandedDefault])
        @endforeach
    </div>
@endif

