<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Symfony\Component\Process\Process;

class LocalDocumentationServer
{
    public function serve(string $root, string $host, int $port): int
    {
        passthru($this->command($root, $host, $port), $status);

        return (int) $status;
    }

    /**
     * @param  list<string>  $watchPaths
     * @param  callable(): int  $rebuild
     */
    public function serveWithWatch(string $root, string $host, int $port, array $watchPaths, callable $rebuild): int
    {
        $server = new Process([PHP_BINARY, '-S', $host.':'.$port, '-t', $root]);
        $server->setTimeout(null);
        $server->start();

        $snapshot = $this->snapshot($watchPaths);

        try {
            while ($server->isRunning()) {
                usleep(500_000);

                $nextSnapshot = $this->snapshot($watchPaths);

                if ($nextSnapshot === $snapshot) {
                    continue;
                }

                $snapshot = $nextSnapshot;

                if ($rebuild() !== 0) {
                    return 1;
                }
            }

            return $server->getExitCode() ?? 0;
        } finally {
            $server->stop();
        }
    }

    private function command(string $root, string $host, int $port): string
    {
        return sprintf(
            '%s -S %s:%d -t %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($host),
            $port,
            escapeshellarg($root),
        );
    }

    /**
     * @param  list<string>  $paths
     * @return array<string, int>
     */
    private function snapshot(array $paths): array
    {
        $snapshot = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $mtime = filemtime($path);

                if (is_int($mtime)) {
                    $snapshot[$path] = $mtime;
                }

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                $mtime = $file->getMTime();
                $snapshot[$file->getPathname()] = $mtime;
            }
        }

        ksort($snapshot);

        return $snapshot;
    }
}
