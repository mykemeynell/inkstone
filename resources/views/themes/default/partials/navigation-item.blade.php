@if($item->children !== [])
    <span class="inkstone-nav-item inkstone-nav-parent {{ $item->active ? 'is-active' : '' }}">{{ $item->title }}</span>
@else
    <a class="inkstone-nav-item {{ $item->active ? 'is-active' : '' }}" href="{{ $item->url }}">{{ $item->title }}</a>
@endif
@if($item->children !== [])
    <div class="inkstone-nav-children">
        @foreach($item->children as $child)
            @include('inkstone::themes.default.partials.navigation-item', ['item' => $child])
        @endforeach
    </div>
@endif
