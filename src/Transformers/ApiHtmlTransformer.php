<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;

final class ApiHtmlTransformer implements Transformer
{
    public function transform(Document $document): Document
    {
        if (! isset($document->metadata['_api_spec'])) {
            return $document;
        }

        $api = $document->metadata['_api'];

        $html = $this->renderApi($api);
        $headings = $this->buildHeadings($api);

        return $document->withParsedContent($html, $document->metadata, $headings, null);
    }

    private function renderApi(array $api): string
    {
        $parts = [];

        $parts[] = '<div class="inkstone-api-docs">';
        $parts[] = $this->renderInfo($api);
        $parts[] = $this->renderEndpoints($api);
        $parts[] = $this->renderSchemas($api);
        $parts[] = '</div>';

        return implode("\n", $parts);
    }

    private function renderInfo(array $api): string
    {
        $html = '';

        if ($api['title'] !== '') {
            $html .= '<h1>'.e($api['title']);

            if ($api['version'] !== '') {
                $html .= ' <span class="inkstone-api-version-badge">v'.e($api['version']).'</span>';
            }

            $html .= '</h1>';
        }

        if ($api['description'] !== '') {
            $html .= '<p class="inkstone-api-description">'.e($api['description']).'</p>';
        }

        if ($api['servers'] !== []) {
            $html .= '<h2 id="servers">Servers</h2>';
            $html .= '<table class="inkstone-api-table"><thead><tr><th>URL</th><th>Description</th></tr></thead><tbody>';

            foreach ($api['servers'] as $server) {
                $desc = $server['description'] !== '' ? e($server['description']) : '—';
                $html .= '<tr><td><code>'.e($server['url']).'</code></td><td>'.$desc.'</td></tr>';
            }

            $html .= '</tbody></table>';
        }

        return $html;
    }

    private function renderEndpoints(array $api): string
    {
        $endpoints = $api['endpoints'];
        $tags = $api['tags'];

        if ($endpoints === []) {
            return '';
        }

        $html = '<h2 id="endpoints">Endpoints</h2>';

        $grouped = $this->groupEndpointsByTag($endpoints, $tags);

        foreach ($grouped as $group) {
            $tagId = $this->idFromTag($group['tag']);
            $html .= '<h3 id="'.$tagId.'">'.e($group['tag']).'</h3>';

            if ($group['description'] !== '') {
                $html .= '<p>'.e($group['description']).'</p>';
            }

            foreach ($group['endpoints'] as $ep) {
                $html .= $this->renderEndpoint($ep);
            }
        }

        return $html;
    }

    private function groupEndpointsByTag(array $endpoints, array $tags): array
    {
        $tagDescriptions = [];

        foreach ($tags as $tag) {
            $tagDescriptions[$tag['name']] = $tag['description'] ?? '';
        }

        $ungrouped = [];
        $grouped = [];

        foreach ($endpoints as $ep) {
            $epTags = $ep['tags'];

            if ($epTags === []) {
                $ungrouped[] = $ep;
            } else {
                foreach ($epTags as $tag) {
                    $grouped[$tag][] = $ep;
                }
            }
        }

        $result = [];

        if ($ungrouped !== []) {
            $result[] = [
                'tag' => 'General',
                'description' => '',
                'endpoints' => $ungrouped,
            ];
        }

        foreach ($grouped as $tag => $eps) {
            $result[] = [
                'tag' => $tag,
                'description' => $tagDescriptions[$tag] ?? '',
                'endpoints' => $eps,
            ];
        }

        return $result;
    }

    private function renderEndpoint(array $ep): string
    {
        $method = strtolower($ep['method']);
        $endpointId = $this->endpointId($ep);
        $deprecated = $ep['deprecated'];

        $html = '<div class="inkstone-api-endpoint'.($deprecated ? ' is-deprecated' : '').'" id="'.$endpointId.'">';
        $html .= '<div class="inkstone-api-endpoint-header">';
        $html .= '<span class="inkstone-api-method is-'.$method.'">'.e($ep['method']).'</span>';
        $html .= '<code class="inkstone-api-path">'.e($ep['path']).'</code>';

        if ($ep['summary'] !== '') {
            $html .= '<span class="inkstone-api-summary">'.e($ep['summary']).'</span>';
        }

        if ($deprecated) {
            $html .= '<span class="inkstone-api-deprecated-badge">deprecated</span>';
        }

        $html .= '</div>';

        $html .= '<div class="inkstone-api-endpoint-body">';

        if ($ep['description'] !== '') {
            $html .= '<p class="inkstone-api-description">'.e($ep['description']).'</p>';
        }

        if ($ep['parameters'] !== []) {
            $html .= $this->renderSection('Parameters', 'parameters', function () use ($ep): string {
                return $this->renderParameters($ep['parameters']);
            });
        }

        if ($ep['requestBody'] !== null) {
            $html .= $this->renderSection('Request Body', 'request-body', function () use ($ep): string {
                return $this->renderRequestBody($ep['requestBody']);
            });
        }

        if ($ep['responses'] !== []) {
            $html .= $this->renderSection('Responses', 'responses', function () use ($ep): string {
                return $this->renderResponses($ep['responses']);
            });
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderSection(string $title, string $id, \Closure $render): string
    {
        return '<details class="inkstone-api-section" '.($id === 'parameters' || $id === 'responses' ? 'open' : '').'>'
            .'<summary class="inkstone-api-section-summary">'.e($title).'</summary>'
            .'<div class="inkstone-api-section-body">'
            .$render()
            .'</div>'
            .'</details>';
    }

    private function renderParameters(array $parameters): string
    {
        $html = '<table class="inkstone-api-table"><thead><tr><th>Name</th><th>In</th><th>Type</th><th>Required</th><th>Description</th></tr></thead><tbody>';

        foreach ($parameters as $param) {
            $required = $param['required'] ? '<span class="inkstone-api-required-badge">required</span>' : 'Optional';
            $type = $param['schema']['type'] ?? 'string';
            $description = $param['description'] !== '' ? e($param['description']) : '—';

            if ($param['deprecated']) {
                $description .= ' <span class="inkstone-api-deprecated-badge">deprecated</span>';
            }

            $html .= '<tr>';
            $html .= '<td><code>'.e($param['name']).'</code></td>';
            $html .= '<td>'.e($param['in']).'</td>';
            $html .= '<td>'.e($type).'</td>';
            $html .= '<td>'.$required.'</td>';
            $html .= '<td>'.$description.'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function renderRequestBody(array $requestBody): string
    {
        $html = '';

        if ($requestBody['description'] !== '') {
            $html .= '<p>'.e($requestBody['description']).'</p>';
        }

        foreach ($requestBody['content'] as $mediaType => $content) {
            $html .= '<p class="inkstone-api-content-type"><span>Content-Type</span> <code>'.e($mediaType).'</code>';

            if ($requestBody['required']) {
                $html .= ' <span class="inkstone-api-required-badge">required</span>';
            }

            $html .= '</p>';

            if ($content['schema'] !== null) {
                $html .= $this->renderSchemaTable($content['schema']);
            }

            if ($content['example'] !== null) {
                $html .= '<pre><code>'.e(json_encode($content['example'], JSON_PRETTY_PRINT)).'</code></pre>';
            }
        }

        return $html;
    }

    private function renderResponses(array $responses): string
    {
        $html = '';

        foreach ($responses as $statusCode => $response) {
            $statusClass = $this->statusClass((int) $statusCode);
            $html .= '<div class="inkstone-api-response">';
            $html .= '<div class="inkstone-api-response-header">';
            $html .= '<span class="inkstone-api-status is-'.$statusClass.'">'.e($statusCode).'</span>';
            $html .= '<span class="inkstone-api-response-description">'.e($response['description']).'</span>';
            $html .= '</div>';

            if ($response['content'] !== []) {
                $html .= '<div class="inkstone-api-response-body">';

                foreach ($response['content'] as $mediaType => $content) {
                    $html .= '<p class="inkstone-api-content-type"><span>Content-Type</span> <code>'.e($mediaType).'</code></p>';

                    if ($content['schema'] !== null) {
                        $html .= $this->renderSchemaTable($content['schema']);
                    }

                    if ($content['example'] !== null) {
                        $html .= '<pre><code>'.e(json_encode($content['example'], JSON_PRETTY_PRINT)).'</code></pre>';
                    }
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    private function renderSchemaTable(array $schema): string
    {
        $type = $schema['type'] ?? 'mixed';

        if ($type === 'object' && isset($schema['properties'])) {
            $html = '<table class="inkstone-api-schema"><thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead><tbody>';
            $required = $schema['required'] ?? [];

            foreach ($schema['properties'] as $propName => $prop) {
                $html .= $this->renderSchemaRow($propName, $prop, $required, 0);
            }

            $html .= '</tbody></table>';

            return $html;
        }

        if ($type === 'array' && isset($schema['items'])) {
            $items = $schema['items'];
            $desc = $schema['description'] ?? '';

            $html = '<p><strong>Type:</strong> array of '.e($items['type'] ?? 'mixed').'</p>';

            if ($desc !== '') {
                $html .= '<p>'.e($desc).'</p>';
            }

            if (isset($items['properties'])) {
                $html .= $this->renderSchemaTable($items);
            }

            return $html;
        }

        $desc = $schema['description'] ?? '';

        $html = '<p><strong>Type:</strong> '.e($type);

        if (isset($schema['format'])) {
            $html .= ' <span class="inkstone-api-format-badge">'.e($schema['format']).'</span>';
        }

        $html .= '</p>';

        if ($desc !== '') {
            $html .= '<p>'.e($desc).'</p>';
        }

        return $html;
    }

    private function renderSchemaRow(string $name, array $prop, array $required, int $depth): string
    {
        $isRequired = in_array($name, $required, true);
        $propType = $prop['type'] ?? 'mixed';
        $desc = $prop['description'] !== '' ? e($prop['description']) : '—';

        if ($prop['nullable']) {
            $propType .= ' | null';
        }

        $html = '<tr class="inkstone-api-schema-row" style="--schema-depth: '.$depth.'">';
        $html .= '<td class="inkstone-api-schema-name"><code>'.e($name).'</code></td>';
        $html .= '<td><code class="inkstone-api-type">'.e($propType).'</code></td>';
        $html .= '<td>'.($isRequired ? '<span class="inkstone-api-required-badge">required</span>' : '').'</td>';
        $html .= '<td>'.$desc.'</td>';
        $html .= '</tr>';

        if (isset($prop['properties'])) {
            $nestedRequired = $prop['required'] ?? [];

            foreach ($prop['properties'] as $nestedName => $nestedProp) {
                $html .= $this->renderSchemaRow($nestedName, $nestedProp, $nestedRequired, $depth + 1);
            }
        }

        if ($propType === 'array' && isset($prop['items']['properties'])) {
            $nestedRequired = $prop['items']['required'] ?? [];

            foreach ($prop['items']['properties'] as $nestedName => $nestedProp) {
                $html .= $this->renderSchemaRow($nestedName, $nestedProp, $nestedRequired, $depth + 1);
            }
        }

        return $html;
    }

    private function renderSchemas(array $api): string
    {
        $schemas = $api['schemas'];

        if ($schemas === []) {
            return '';
        }

        $html = '<h2 id="schemas">Schemas</h2>';

        foreach ($schemas as $name => $schema) {
            $schemaId = $this->idFromTag($name);
            $html .= '<h3 id="'.$schemaId.'">'.e($name).'</h3>';
            $html .= $this->renderSchemaTable($schema);
        }

        return $html;
    }

    private function buildHeadings(array $api): array
    {
        $headings = [];
        $position = 0;

        if ($api['servers'] !== []) {
            $headings[] = new Heading(2, 'Servers', 'servers', $position++);
        }

        if ($api['endpoints'] !== []) {
            $headings[] = new Heading(2, 'Endpoints', 'endpoints', $position++);

            $grouped = $this->groupEndpointsByTag($api['endpoints'], $api['tags']);

            foreach ($grouped as $group) {
                $tagId = $this->idFromTag($group['tag']);
                $headings[] = new Heading(3, $group['tag'], $tagId, $position++);

                foreach ($group['endpoints'] as $ep) {
                    $epId = $this->endpointId($ep);
                    $label = $ep['method'].' '.$ep['path'];
                    $headings[] = new Heading(4, $label, $epId, $position++);
                }
            }
        }

        if ($api['schemas'] !== []) {
            $headings[] = new Heading(2, 'Schemas', 'schemas', $position++);

            foreach ($api['schemas'] as $name => $schema) {
                $schemaId = $this->idFromTag($name);
                $headings[] = new Heading(3, $name, $schemaId, $position++);
            }
        }

        return $headings;
    }

    private function endpointId(array $ep): string
    {
        $path = trim($ep['path'], '/');
        $path = str_replace(['/', '{', '}'], ['-', '', ''], $path);

        return strtolower($ep['method']).'-'.$path;
    }

    private function idFromTag(string $tag): string
    {
        return strtolower(str_replace([' ', '/', '\\'], '-', $tag));
    }

    private function statusClass(int $statusCode): string
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return 'success';
        }

        if ($statusCode >= 300 && $statusCode < 400) {
            return 'redirect';
        }

        if ($statusCode >= 400 && $statusCode < 500) {
            return 'client-error';
        }

        return 'server-error';
    }
}
