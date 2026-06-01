<?php

declare(strict_types=1);

namespace Inkstone\Services;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema as OpenApiSchema;

final class ApiSpecDocumentFactory
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $specPath): array
    {
        $openApi = $this->readSpec($specPath);

        return [
            'title' => $openApi->info->title ?? '',
            'version' => $openApi->info->version ?? '',
            'description' => $openApi->info->description ?? '',
            'servers' => $this->resolveServers($openApi),
            'tags' => $this->resolveTags($openApi),
            'endpoints' => $this->resolveEndpoints($openApi),
            'schemas' => $this->resolveSchemas($openApi),
        ];
    }

    private function readSpec(string $path): OpenApi
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $openApi = match ($extension) {
            'json' => Reader::readFromJsonFile($path, OpenApi::class, true),
            default => Reader::readFromYamlFile($path, OpenApi::class, true),
        };

        if (! $openApi instanceof OpenApi) {
            throw new \RuntimeException("Failed to parse OpenAPI spec at: {$path}");
        }

        return $openApi;
    }

    /**
     * @return list<array{url: string, description: string}>
     */
    private function resolveServers(OpenApi $openApi): array
    {
        $servers = [];

        foreach ($openApi->servers as $server) {
            $servers[] = [
                'url' => $server->url,
                'description' => $server->description ?? '',
            ];
        }

        return $servers;
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    private function resolveTags(OpenApi $openApi): array
    {
        $tags = [];

        foreach ($openApi->tags as $tag) {
            $tags[] = [
                'name' => $tag->name,
                'description' => $tag->description ?? '',
            ];
        }

        return $tags;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveEndpoints(OpenApi $openApi): array
    {
        $endpoints = [];

        foreach ($openApi->paths as $path => $pathItem) {
            if (! $pathItem instanceof PathItem) {
                continue;
            }

            $operations = $this->getOperations($pathItem);

            foreach ($operations as $method => $operation) {
                if (! $operation instanceof Operation) {
                    continue;
                }

                $endpoints[] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'summary' => $operation->summary ?? '',
                    'description' => $operation->description ?? '',
                    'operationId' => $operation->operationId ?? '',
                    'tags' => $operation->tags ?? [],
                    'parameters' => $this->resolveParameters($operation),
                    'requestBody' => $this->resolveRequestBody($operation),
                    'responses' => $this->resolveResponses($operation),
                    'deprecated' => $operation->deprecated ?? false,
                    'security' => $operation->security ?? [],
                ];
            }
        }

        return $endpoints;
    }

    /**
     * @return array<string, Operation>
     */
    private function getOperations(PathItem $pathItem): array
    {
        $operations = [];

        foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'] as $method) {
            $operation = $pathItem->{$method};

            if ($operation instanceof Operation) {
                $operations[$method] = $operation;
            }
        }

        return $operations;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveParameters(Operation $operation): array
    {
        $parameters = [];

        foreach ($operation->parameters as $param) {
            $schema = $param->schema;
            $parameters[] = [
                'name' => $param->name,
                'in' => $param->in,
                'required' => $param->required ?? false,
                'description' => $param->description ?? '',
                'schema' => $schema !== null ? $this->simplifySchema($schema) : ['type' => 'string'],
                'example' => $param->example,
                'deprecated' => $param->deprecated ?? false,
            ];
        }

        return $parameters;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveRequestBody(Operation $operation): ?array
    {
        $requestBody = $operation->requestBody;

        if ($requestBody === null) {
            return null;
        }

        $content = [];

        foreach ($requestBody->content as $mediaType => $mediaTypeObj) {
            $schema = $mediaTypeObj->schema;
            $content[$mediaType] = [
                'schema' => $schema !== null ? $this->simplifySchema($schema) : null,
                'example' => $mediaTypeObj->example,
            ];
        }

        return [
            'required' => $requestBody->required ?? false,
            'description' => $requestBody->description ?? '',
            'content' => $content,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveResponses(Operation $operation): array
    {
        $responses = [];

        foreach ($operation->responses as $statusCode => $response) {
            $content = [];

            foreach ($response->content as $mediaType => $mediaTypeObj) {
                $schema = $mediaTypeObj->schema;
                $content[$mediaType] = [
                    'schema' => $schema !== null ? $this->simplifySchema($schema) : null,
                    'example' => $mediaTypeObj->example,
                ];
            }

            $responses[$statusCode] = [
                'description' => $response->description ?? '',
                'content' => $content,
            ];
        }

        return $responses;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveSchemas(OpenApi $openApi): array
    {
        $schemas = [];

        if ($openApi->components === null || $openApi->components->schemas === null) {
            return $schemas;
        }

        foreach ($openApi->components->schemas as $name => $schema) {
            if ($schema instanceof OpenApiSchema) {
                $schemas[$name] = $this->simplifySchema($schema);
            }
        }

        return $schemas;
    }

    /**
     * @return array<string, mixed>
     */
    private function simplifySchema(OpenApiSchema $schema): array
    {
        $result = [
            'type' => $schema->type,
            'description' => $schema->description ?? '',
            'nullable' => $schema->nullable ?? false,
            'format' => $schema->format,
        ];

        if ($schema->type === 'object' && $schema->properties !== null) {
            $properties = [];

            foreach ($schema->properties as $propName => $propSchema) {
                if ($propSchema instanceof OpenApiSchema) {
                    $properties[$propName] = $this->simplifySchema($propSchema);
                }
            }

            $result['properties'] = $properties;
            $result['required'] = $schema->required ?? [];
        }

        if ($schema->type === 'array' && $schema->items instanceof OpenApiSchema) {
            $result['items'] = $this->simplifySchema($schema->items);
        }

        if ($schema->enum !== null) {
            $result['enum'] = $schema->enum;
        }

        $result['example'] = $schema->example;

        return $result;
    }
}
