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

        return response()->file($file, [
            'Content-Type' => $this->contentTypeFor($file),
        ]);
    }

    private function contentTypeFor(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return match ($extension) {
            'css' => 'text/css',
            'js', 'mjs' => 'application/javascript',
            'json', 'map' => 'application/json',
            'xml' => 'application/xml',
            'txt' => 'text/plain',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'ico' => 'image/x-icon',
            'wasm' => 'application/wasm',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'pdf' => 'application/pdf',
            default => $this->fallbackContentType($file),
        };
    }

    private function fallbackContentType(string $file): string
    {
        $mime = mime_content_type($file);

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }
}
