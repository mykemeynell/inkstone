<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Parsers\CommonMarkMarkdownParser;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\HeadingAnchorTransformer;
use Inkstone\Transformers\SyntaxHighlightTransformer;

final class RenderedHtmlSnapshotTest extends TestCase
{
    public function test_rendered_html_matches_snapshot(): void
    {
        $document = new Document(
            sourcePath: 'docs/snapshot.md',
            relativePath: 'snapshot.md',
            slug: 'snapshot',
            url: '/docs/snapshot',
            markdown: "# Snapshot\n\n```php filename=snapshot.php {2}\necho 'one';\necho 'two';\n```\n",
        );

        $document = (new CommonMarkMarkdownParser)->parse($document);
        $document = (new HeadingAnchorTransformer)->transform($document);
        $document = (new SyntaxHighlightTransformer)->transform($document);

        $expected = file_get_contents(__DIR__.'/../fixtures/snapshots/rendered-basic.html');

        $this->assertSame($expected, $document->html);
    }
}
