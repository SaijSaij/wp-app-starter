<?php

declare(strict_types=1);

namespace Saij\WpAppStarter\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saij\WpAppStarter\Answers;
use Saij\WpAppStarter\Questions;

final class QuestionsTest extends TestCase
{
    public function testAThemeWithDefaults(): void
    {
        $prompt = new FakePrompt(['package' => 'acme/my-theme', 'namespace' => 'acme']);

        $answers = (new Questions($prompt))->ask('my-theme');

        self::assertSame(
            [Answers::THEME, 'my-theme', 'My Theme', 'acme/my-theme', 'Acme\\MyTheme', 'acme', 'functions.php'],
            [$answers->getKind(), $answers->getSlug(), $answers->getName(), $answers->getPackage(), $answers->getPhpNamespace(), $answers->getNamespace(), $answers->getBootFile()],
        );
        self::assertSame([true, true, true, true], [$answers->hasExample(), $answers->hasQuality(), $answers->hasCi(), $answers->hasE2e()]);
        self::assertSame([null, null, null], [$answers->getParent(), $answers->getPrefix(), $answers->getWithTheme()]);
        // A theme is asked neither for a parent, a prefix nor a theme release.
        self::assertSame(['kind', 'name', 'package', 'description', 'author', 'php-namespace', 'namespace', 'example', 'quality', 'ci', 'e2e'], $prompt->getAsked());
        self::assertContains('Slug (the directory name): <comment>my-theme</comment>', $prompt->getSaid());
    }

    public function testThereIsNoDefaultForTheNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No answer for namespace.');

        (new Questions(new FakePrompt(['package' => 'acme/my-theme'])))->ask('my-theme');
    }

    public function testTheDirectoryNameMustBeAValidSlug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The directory name is the slug of the project. "My Theme" is no valid slug');

        (new Questions(new FakePrompt([])))->ask('My Theme');
    }

    public function testAPluginGetsAPrefixFromItsNamespaceAndAnOptionalThemeRelease(): void
    {
        $answers = (new Questions(new FakePrompt(['kind' => 'plugin', 'package' => 'acme/shop', 'namespace' => 'acme-shop2'])))->ask('acme-shop');

        self::assertSame(['acmeshop', null, 'acme-shop.php'], [$answers->getPrefix(), $answers->getWithTheme(), $answers->getBootFile()]);

        $answers = (new Questions(new FakePrompt(['kind' => 'plugin', 'package' => 'acme/shop', 'namespace' => 'acme', 'with-theme' => 'acme/theme@v1.0.0'])))->ask('acme-shop');
        self::assertSame('acme/theme@v1.0.0', $answers->getWithTheme());
    }

    public function testAChildThemeNeedsItsParentAndWithCiItsRelease(): void
    {
        $answers = (new Questions(new FakePrompt(['kind' => 'child-theme', 'parent' => 'acme-theme', 'package' => 'acme/child', 'namespace' => 'acme-child', 'with-theme' => 'acme/acme-theme@v2.0.0'])))->ask('acme-child');
        self::assertSame(['acme-theme', 'acme/acme-theme@v2.0.0'], [$answers->getParent(), $answers->getWithTheme()]);

        // Without the release workflow, no theme release is asked.
        $answers = (new Questions(new FakePrompt(['kind' => 'child-theme', 'parent' => 'acme-theme', 'package' => 'acme/child', 'namespace' => 'acme-child', 'ci' => 'no'])))->ask('acme-child');
        self::assertNull($answers->getWithTheme());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('parent: The parent theme needs another slug than the child theme.');
        (new Questions(new FakePrompt(['kind' => 'child-theme', 'parent' => 'acme-child'])))->ask('acme-child');
    }
}
