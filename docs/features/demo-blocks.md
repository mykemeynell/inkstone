---
title: Demo Blocks
order: 4
---

# Demo Blocks

Demo blocks render source and output together at build time.

They are static documentation examples, not a live playground.

## Basic Syntax

Use a fenced code block with a `demo:` language prefix:

````markdown
```demo:php
collect(['Laravel', 'Docs', 'Inkstone'])
    ->map(fn ($item) => strtoupper($item))
    ->all();
```
````

Rendered result:
```demo:php
collect(['Laravel', 'Docs', 'Inkstone'])
    ->map(fn ($item) => strtoupper($item))
    ->all();
```

## Supported Demo Languages

| Language | Behavior |
| --- | --- |
| `demo:php` | Executes PHP at build time and renders the result |
| `demo:markdown` or `demo:md` | Renders Markdown to HTML |
| `demo:html` | Renders the HTML as output |
| `demo:blade` | Renders through Laravel's Blade compiler when available |
| other languages | Render source only as static examples |

## Markdown Demos

Markdown demos are useful for documenting Markdown rendering itself:

````demo:markdown
```php
echo 'Nested code stays intact';
```
````

## Expected Exceptions

Use `throws` when an exception is expected:

````markdown
```demo:php throws
throw new RuntimeException('Expected failure');
```
````

Rendered result:

```demo:php throws
throw new RuntimeException('Expected failure');
```

You can also restrict expected exception classes:

````markdown
```demo:php throws:InvalidArgumentException,RuntimeException
throw new RuntimeException('Expected failure');
```
````

Unexpected exceptions fail the build.

## Void Output

Use `void` when the example intentionally has no visible output:

````markdown
```demo:php void
usleep(1);
```
````

Rendered result:

```demo:php void
usleep(1);
```

## Result Rendering

Inkstone includes renderers for:

- renderable values
- primitive values
- arrays
- collections
- Eloquent models
- objects
- exceptions
- enums

Arrays and collections are rendered as formatted JSON code blocks.

## Sandbox Configuration

```php
'demos' => [
    'enabled' => true,
    'describe_void_output' => false,
    'sandbox' => [
        'enabled' => true,
        'timeout' => 5,
        'memory_limit' => '128M',
        'allow_filesystem_writes' => false,
        'allow_process_execution' => false,
    ],
],
```

## Stack Traces

When `demos.show_stack_traces` is enabled, demo exceptions render a collapsible stack trace:

```php
'demos' => [
    'show_stack_traces' => true,
],
```

## Disposable Database

When `demos.use_disposable_database` is enabled, each PHP demo receives a fresh in-memory SQLite database:

```php
'demos' => [
    'use_disposable_database' => true,
    'database' => [
        'connection' => 'inkstone_demo',
        'database' => ':memory:',
    ],
],
```

The database is created before each demo and destroyed after execution.

Inkstone blocks obvious unsafe PHP functions such as process execution and filesystem writes.

Do not execute untrusted demo code.
