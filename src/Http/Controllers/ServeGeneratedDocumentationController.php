<?php

declare(strict_types=1);

namespace Inkstone\Http\Controllers;

use Inkstone\Services\GeneratedDocumentationFileServer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ServeGeneratedDocumentationController
{
    public function __invoke(GeneratedDocumentationFileServer $server, ?string $inkstonePath = null): BinaryFileResponse
    {
        $file = $server->resolve($inkstonePath ?? '');

        if ($file === null) {
            abort(404);
        }

        return response()->file($file);
    }
}
