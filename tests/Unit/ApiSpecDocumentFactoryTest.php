<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Services\ApiSpecDocumentFactory;
use PHPUnit\Framework\TestCase;

final class ApiSpecDocumentFactoryTest extends TestCase
{
    public function test_it_parses_specs_from_relative_paths(): void
    {
        $factory = new ApiSpecDocumentFactory;

        $api = $factory->parse('tests/fixtures/docs/openapi.yaml');

        $this->assertSame('Users API', $api['title']);
        $this->assertCount(3, $api['endpoints']);
    }
}
