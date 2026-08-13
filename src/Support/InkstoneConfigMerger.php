<?php

declare(strict_types=1);

namespace Inkstone\Support;

final class InkstoneConfigMerger
{
    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    public static function merge(array $base, array $override): array
    {
        $merged = array_replace_recursive($base, $override);

        if (array_key_exists('extensions', $override)) {
            $merged['extensions'] = $override['extensions'];
        }

        return $merged;
    }
}
