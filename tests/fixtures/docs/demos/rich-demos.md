---
title: Embedded Demos
description: Fixture document for testing embedded demo rendering and component execution
order: 50
---

# Embedded Demos

This document exists to test embedded demo rendering functionality.

The parser should:
- detect custom demo fences
- extract language and runtime metadata
- preserve raw component syntax
- avoid escaping Blade directives
- support nested component structures
- support executable PHP snippets
- support syntax highlighting
- support preview rendering

---

# Blade Components

## Basic Blade Component

```demo:blade
<x-alert type="success">
    Documentation generated successfully.
</x-alert>
```

---

## Navbar Component With Complex Props

```demo:blade
<x-navbar
    :items="[
        '#example1' => 'Nav 1',
        '#example2' => 'Nav 2',
        '#example3' => 'Nav 3',
    ]"
/>
```

---

## Nested Blade Components

```demo:blade
<x-card>
    <x-slot name="title">
        Dashboard
    </x-slot>

    <x-button variant="primary">
        Save Changes
    </x-button>
</x-card>
```

---

## Anonymous Components

```demo:blade
<x-button>
    Click Me
</x-button>
```

---

## Dynamic Components

```demo:blade
<x-dynamic-component
    :component="$component"
/>
```

---

## Conditional Rendering

```demo:blade
@if($user->isAdmin())
    <x-admin.panel />
@endif
```

---

# Livewire Components

## Livewire Tag Syntax

```demo:blade
<livewire:user-profile />
```

---

## Livewire Directive Syntax

```demo:blade
@livewire('path.to.component')
```

---

## Livewire With Parameters

```demo:blade
<livewire:user-table
    :users="$users"
    :paginate="true"
/>
```

---

## Volt Component Example

```demo:blade
<livewire:settings.profile />
```

---

# Alpine.js Examples

## Alpine State

```demo:blade
<div x-data="{ open: false }">
    <button @click="open = !open">
        Toggle
    </button>

    <div x-show="open">
        Example Content
    </div>
</div>
```

---

## Alpine With Blade

```demo:blade
<x-dropdown>
    <div x-data="{ open: false }">
        <button @click="open = !open">
            Menu
        </button>

        <div x-show="open">
            Dropdown Content
        </div>
    </div>
</x-dropdown>
```

---

# PHP Runtime Demos

## Error Handling

```demo:php
try {
    throw new Exception('Something went wrong!');
} catch (Exception $e) {
    echo $e->getMessage();
}
```

## Runtime Errors During Build

As $unknownVariable is not defined, this should result in an error when the demo is built to the developer indicating:
- The type of demo (PHP/Blade/etc.)
- The markdown file path that contains the demo
- The line number where the error occurred
- The error message

```demo:php
echo $unknownVariable;
```

## Throwables and Exceptions

```demo:php throws
throw new Exception("This is a fixture exception");
```

```php
// ⚠️ \Exception: "This is a fixture exception"
```

The `throws` attribute tells the builder that the demo is expected to throw a Throwable instance.
This will result in the exception type and message being displayed in the demo preview, rather than the build failing.

To only allow specified Throwables in your demo;

```demo:php throws:\App\Exceptions\CustomException
throw new \App\Exceptions\CustomException("This is a custom exception");
```

```php
// ⚠️ \App\Exceptions\CustomException: "This is a fixture exception"
```

---

## Basic PHP Demo

```demo:php
echo (new Foo)->bar();
```

---

## Service Container Resolution

```demo:php
$generator = app(\Inkstone\Services\Builder::class);

echo $generator->build();
```

---

## Collection Example

```demo:php
collect([
    'Laravel',
    'Docs',
    'Generator',
])->map(fn ($item) => strtoupper($item));
```

---

## Eloquent Example

```demo:php
User::query()
    ->latest()
    ->take(5)
    ->get();
```

---

# Raw HTML Demos

## HTML Component

```demo:html
<div class="rounded-lg border p-4">
    <h2>Hello World</h2>
    <p>Fixture content.</p>
</div>
```

---

# JavaScript Demo

## Interactive Example

```demo:js
document.querySelectorAll('[data-copy]').forEach(button => {
    button.addEventListener('click', () => {
        console.log('Copied!');
    });
});
```

---

# Vue Example

```demo:vue
<template>
    <button @click="count++">
        {{ count }}
    </button>
</template>

<script setup>
import { ref } from 'vue'

const count = ref(0)
</script>
```

---

# React Example

```demo:jsx
export default function Button() {
    return (
        <button>
            Click Me
        </button>
    )
}
```

---

# Escaping Edge Cases

## Blade Echoes

```demo:blade
{{ $user->name }}
{!! $html !!}
```

---

## Escaped Blade

```demo:blade
@{{ name }}
```

---

## Nested Backticks

````demo:blade
<x-code-example>
```php
echo "Nested markdown";
```
</x-code-example>
````

---

## Renderable Objects and Literals

When a demo returns an instance of Renderable, it will be rendered directly in the preview.

If a demo does not return a Renderable instance, the demo will be rendered using the ```\Symfony\Component\VarDumper\VarDumper::dump()``` method.

For example, adding this to your markdown file:

```demo:php
app('config')->get('demo.config');
```

Will add this to your generated docs:

```php
[
    "commands" => [],
    "alias" => [],
    "dont_alias" => [
        "App\\Demo",
    ],
    "trust_project" => "always",
]
```

Where the code block language is taken from the demo's metadata (`:php`).

## Voids

If no output is generated from a demo - ie. the method/function called has a void return type, then an output 
describing this is displayed instead.

```demo:php
Bar::BazVoid();
```

```php
// (void) Bar::BazVoid() 
```

You can suppress the output of void methods by disabling void demo descriptions in the builder configuration `demos.describe_void_output`, or
by adding the `void` attribute to the demo block.

```demo:php void
Bar::BazVoid();
```
