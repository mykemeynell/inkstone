<?php

declare(strict_types=1);

namespace Inkstone\Http\Controllers;

use Inkstone\Services\GeneratedDocumentationFileServer;
use Inkstone\Services\GeneratedDocumentationUrlRewriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class ServeGeneratedDocumentationController
{
    public function __invoke(
        GeneratedDocumentationFileServer $server,
        GeneratedDocumentationUrlRewriter $rewriter,
        ?string $inkstonePath = null,
    ): BinaryFileResponse|Response {
        $file = $server->resolve($inkstonePath ?? '');

        if ($file === null) {
            abort(404);
        }

        if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
            $contents = file_get_contents($file);

            if (! is_string($contents)) {
                abort(404);
            }

            return response($rewriter->rewrite($contents), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response()->file($file);
    }
}
