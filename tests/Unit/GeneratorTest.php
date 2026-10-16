<?php

declare(strict_types=1);

namespace Saij\WpAppStarter\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saij\WpAppStarter\Answers;
use Saij\WpAppStarter\Generator;

final class GeneratorTest extends TestCase
{
    private const SKELETON = __DIR__ . '/../../skeleton';

    public function testIfElseAndNestedBlocks(): void
    {
        $generator = self::generator(self::answers(Answers::PLUGIN));

        self::assertSame(
            "a\nplugin __SLUG__ is acme-plugin\nwith example\nend\n",
            str_replace('acme-plugin is', '__SLUG__ is', $generator->render("a\n@@if theme\ntheme\n@@else\nplugin __SLUG__ is __SLUG__\n@@if example\nwith example\n@@endif\n@@endif\n@@if !plugin\nnot\n@@endif\nend\n")),
        );
    }

    public function testMagicConstantsOfPhpAreNoPlaceholders(): void
    {
        self::assertSame("__DIR__ __FILE__ __NAMESPACE__\n", self::generator(self::answers(Answers::THEME))->render("__DIR__ __FILE__ __NAMESPACE__\n"));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function brokenTemplates(): iterable
    {
        yield 'unknown flag' => ["@@if colour\n@@endif\n", 'template, line 1: unknown flag "colour".'];
        yield '@@endif without @@if' => ["@@endif\n", 'template, line 1: @@endif without @@if.'];
        yield '@@else without @@if' => ["@@else\n", 'template, line 1: @@else without @@if.'];
        yield '@@if without @@endif' => ["@@if theme\n", 'template: @@if without @@endif.'];
        yield 'misspelt placeholder' => ["__SLGU__\n", 'template: unknown placeholder __SLGU__.'];
    }

    #[DataProvider('brokenTemplates')]
    public function testBrokenTemplatesFail(string $template, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        self::generator(self::answers(Answers::THEME))->render($template);
    }

    /**
     * Every kind with every combination of options.
     *
     * @return iterable<string, array{Answers}>
     */
    public static function projects(): iterable
    {
        foreach ([Answers::THEME, Answers::CHILD_THEME, Answers::PLUGIN] as $kind) {
            for ($options = 0; $options < 16; $options++) {
                $flags = array_map(static fn(int $bit): bool => ($options & $bit) !== 0, [1, 2, 4, 8]);
                yield sprintf('%s %04b', $kind, $options) => [self::answers($kind, ...$flags)];
            }
        }
    }

    #[DataProvider('projects')]
    public function testEveryProjectRendersCompletely(Answers $answers): void
    {
        $files = self::generator($answers)->build(self::SKELETON);

        foreach ($files as $path => $content) {
            self::assertDoesNotMatchRegularExpression('/@@(if|else|endif)\b|__(SLUG|NAME|PACKAGE|PHP_NAMESPACE|WP_NAMESPACE|PREFIX|TW|PARENT)__/', $content, $path);
            self::assertStringNotContainsString('/_', '/' . $path, 'A leading "_" is renamed: ' . $path);
        }
        self::assertArrayHasKey($answers->getBootFile(), $files);
        self::assertSame($answers->hasExample(), isset($files['src/Block/Teaser/Teaser.php']));
        self::assertSame(!$answers->hasExample(), isset($files['src/.gitkeep']));
        self::assertSame($answers->hasQuality(), isset($files['phpstan.dist.neon'], $files['.php-cs-fixer.dist.php'], $files['eslint.config.cjs']));
        self::assertSame($answers->hasCi(), isset($files['.github/workflows/release.yml']));
        self::assertSame($answers->hasE2e(), isset($files['tests/dev.pools'], $files['tests/release.pools'], $files['tests/e2e/specs/smoke.spec.js']));
        self::assertSame($answers->isTheme(), isset($files['theme.json'], $files['templates/index.html']));
        self::assertSame(!$answers->isPlugin(), isset($files['style.css']));
    }

    public function testComposerJsonOfAPlugin(): void
    {
        $files = self::generator(self::answers(Answers::PLUGIN))->build(self::SKELETON);
        $composer = self::composer($files['composer.json']);

        self::assertSame('wordpress-plugin', $composer['type']);
        self::assertSame(['php' => '>=8.5', 'saij/wp-app-framework' => '^0.16'], $composer['require']);
        self::assertSame(['Acme\\Plugin\\' => 'src/'], $composer['autoload']['psr-4']);
        self::assertSame(['namespace' => 'acme-plugin'], $composer['extra']['wp-app-framework']);
        self::assertSame('Acme\\Plugin\\Vendor\\', $composer['extra']['strauss']['namespace_prefix']);
        self::assertSame('Acme_Plugin_Vendor_', $composer['extra']['strauss']['classmap_prefix']);
        self::assertSame(['src', 'acme-plugin.php'], $composer['extra']['strauss']['update_call_sites']);
        self::assertStringContainsString('prefix(acme)', $files['assets/styles.css']);
        self::assertStringContainsString("'acme:p-4'", $files['src/Block/Teaser/Teaser.twig']);
        self::assertStringContainsString('consumer-release.yml@v0.16.0', $files['.github/workflows/release.yml']);
        self::assertStringNotContainsString('with-theme:', $files['.github/workflows/release.yml']);
        self::assertStringContainsString("plugins acme-plugin\ne2e acme-plugin", $files['tests/dev.pools']);
    }

    public function testComposerJsonWithoutQualityToolsHasNoLint(): void
    {
        $files = self::generator(self::answers(Answers::THEME, quality: false))->build(self::SKELETON);
        $composer = self::composer($files['composer.json']);
        /** @var array{scripts: array<string, string>} $package */
        $package = json_decode($files['package.json'], true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('require-dev', $composer);
        self::assertArrayNotHasKey('lint', $composer['scripts']);
        self::assertArrayNotHasKey('lint', $package['scripts']);
        self::assertStringNotContainsString('phpstan', $files['.gitattributes']);
    }

    public function testAChildThemeNamesItsParent(): void
    {
        $files = self::generator(self::answers(Answers::CHILD_THEME))->build(self::SKELETON);

        self::assertStringContainsString("Template: acme-theme\n", $files['style.css']);
        self::assertStringContainsString('ChildTheme::boot();', $files['functions.php']);
        self::assertStringContainsString('with-theme: acme/acme-theme@v1.0.0', $files['.github/workflows/release.yml']);
        self::assertStringContainsString("themes acme-theme acme-child\nactivate acme-child", $files['tests/dev.pools']);
        self::assertStringContainsString('theme-zip acme-child/tests/parent/*.zip', $files['tests/release.pools']);
        self::assertStringContainsString('/tests/parent/*.zip', $files['.gitignore']);
    }

    /**
     * @return array{type: string, require: array<string, string>, autoload: array{psr-4: array<string, string>}, scripts: array<string, mixed>, extra: array{wp-app-framework: array<string, string>, strauss: array<string, mixed>}}
     */
    private static function composer(string $json): array
    {
        /** @var array{type: string, require: array<string, string>, autoload: array{psr-4: array<string, string>}, scripts: array<string, mixed>, extra: array{wp-app-framework: array<string, string>, strauss: array<string, mixed>}} The shape Generator::composerJson() writes. */
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    private static function generator(Answers $answers): Generator
    {
        return new Generator($answers, '^0.16', 'v0.16.0');
    }

    private static function answers(string $kind, bool $example = true, bool $quality = true, bool $ci = true, bool $e2e = true): Answers
    {
        return match ($kind) {
            Answers::THEME => new Answers($kind, 'acme-theme', 'Acme Theme', 'acme/theme', 'A theme.', 'Acme', 'Acme\\Theme', 'acme-theme', null, null, null, $example, $quality, $ci, $e2e),
            Answers::CHILD_THEME => new Answers($kind, 'acme-child', 'Acme Child', 'acme/child', '', '', 'Acme\\Child', 'acme-child', 'acme-theme', null, $ci ? 'acme/acme-theme@v1.0.0' : null, $example, $quality, $ci, $e2e),
            default => new Answers($kind, 'acme-plugin', 'Acme Plugin', 'acme/plugin', 'A plugin.', '', 'Acme\\Plugin', 'acme-plugin', null, 'acme', null, $example, $quality, $ci, $e2e),
        };
    }
}
