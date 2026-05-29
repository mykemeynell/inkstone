<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Tests\TestCase;

final class LaravelBoostIntegrationTest extends TestCase
{
    public function test_it_ships_laravel_boost_guidelines_and_skill_resources(): void
    {
        $root = dirname(__DIR__, 2);
        $guidelines = $root.'/resources/boost/guidelines/core.blade.php';
        $skill = $root.'/resources/boost/skills/inkstone-documentation/SKILL.md';

        $this->assertFileExists($guidelines);
        $this->assertFileExists($skill);

        $guidelinesContents = file_get_contents($guidelines) ?: '';
        $skillContents = file_get_contents($skill) ?: '';

        $this->assertStringContainsString('php artisan docs:build', $guidelinesContents);
        $this->assertStringContainsString('vendor/bin/inkstone docs:build', $guidelinesContents);
        $this->assertStringContainsString('config/inkstone.php', $guidelinesContents);
        $this->assertStringContainsString('build/docs', $guidelinesContents);

        $this->assertStringStartsWith("---\nname: inkstone-documentation\n", $skillContents);
        $this->assertStringContainsString('docs/README.md', $skillContents);
        $this->assertStringContainsString('folder `index.md` files', $skillContents);
        $this->assertStringContainsString('vendor/bin/inkstone docs:ai-prompt', $skillContents);
    }

    public function test_laravel_boost_is_not_a_required_runtime_dependency(): void
    {
        $composer = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json') ?: '{}',
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertArrayNotHasKey('laravel/boost', $composer['require'] ?? []);
    }
}
