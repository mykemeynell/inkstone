<?php

declare(strict_types=1);

namespace Inkstone\Support;

final class Slugger
{
    public function slug(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[^\pL\pN]+/u', '-', $value) ?? $value;
        $value = trim($value, '-');
        $value = strtolower($value);

        return $value !== '' ? $value : 'section';
    }
}
