@props([
    'type' => 'button'
])

@if($type === 'button')
    <button class="inkstone-icon-button is-subtle inkstone-search-trigger" type="button" aria-label="Search docs" title="Search docs" data-inkstone-search-open>
        <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    </button>
@elseif($type === 'input')
    <div class="inkstone-search-input inkstone-search-trigger" aria-label="Search docs" title="Search docs" data-inkstone-search-open>
        <span>Search...</span>
        <span class="inkstone-keyboard-shortcut">⌘K</span>
    </div>
@endif
