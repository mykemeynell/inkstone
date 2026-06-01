<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Services\ApiSpecDocumentFactory;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\ApiHtmlTransformer;

final class ApiHtmlTransformerTest extends TestCase
{
    private ApiHtmlTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new ApiHtmlTransformer;
    }

    public function test_it_returns_unchanged_document_when_not_api_spec(): void
    {
        $document = new Document(
            sourcePath: 'docs/test.md',
            relativePath: 'test.md',
            slug: 'test',
            url: '/docs/test',
            html: '<p>Hello</p>',
        );

        $result = $this->transformer->transform($document);

        $this->assertSame('<p>Hello</p>', $result->html);
    }

    public function test_it_generates_html_from_api_metadata(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $this->assertStringContainsString('Users API', $result->html);
        $this->assertStringContainsString('GET', $result->html);
        $this->assertStringContainsString('/users', $result->html);
        $this->assertStringContainsString('POST', $result->html);
        $this->assertStringContainsString('/users/{id}', $result->html);
    }

    public function test_it_generates_endpoint_sections(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $this->assertStringContainsString('inkstone-api-endpoint', $result->html);
        $this->assertStringContainsString('inkstone-api-method is-get', $result->html);
        $this->assertStringContainsString('inkstone-api-method is-post', $result->html);
    }

    public function test_it_generates_parameters_table(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $this->assertStringContainsString('Parameters', $result->html);
        $this->assertStringContainsString('page', $result->html);
        $this->assertStringContainsString('per_page', $result->html);
    }

    public function test_it_generates_responses(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $this->assertStringContainsString('Responses', $result->html);
        $this->assertStringContainsString('200', $result->html);
        $this->assertStringContainsString('404', $result->html);
    }

    public function test_it_generates_schemas_section(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $this->assertStringContainsString('Schemas', $result->html);
        $this->assertStringContainsString('User', $result->html);
        $this->assertStringContainsString('CreateUser', $result->html);
    }

    public function test_it_builds_headings_for_toc(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $titles = array_map(
            static fn ($h) => $h->text,
            $result->headings,
        );

        $this->assertContains('Servers', $titles);
        $this->assertContains('Endpoints', $titles);
        $this->assertContains('Users', $titles);
        $this->assertContains('GET /users', $titles);
        $this->assertContains('Schemas', $titles);
    }

    public function test_it_preserves_api_metadata(): void
    {
        $document = $this->createApiDocument();

        $result = $this->transformer->transform($document);

        $this->assertTrue($result->metadata['_api_spec']);
        $this->assertArrayHasKey('_api', $result->metadata);
    }

    private function createApiDocument(): Document
    {
        $factory = new ApiSpecDocumentFactory;
        $apiData = $factory->parse(__DIR__.'/../fixtures/docs/openapi.yaml');

        return new Document(
            sourcePath: __DIR__.'/../fixtures/docs/openapi.yaml',
            relativePath: 'api/openapi.yaml',
            slug: 'api/openapi-yaml',
            url: '/docs/api/openapi-yaml',
            markdown: '',
            metadata: [
                '_api_spec' => true,
                '_api' => $apiData,
                'title' => $apiData['title'],
            ],
        );
    }
}
