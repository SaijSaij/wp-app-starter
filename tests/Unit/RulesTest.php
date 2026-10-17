<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebCodingCat\WpAppStarter\Rules;

final class RulesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function values(): iterable
    {
        yield 'slug' => ['slug', 'my-theme_2', true];
        yield 'slug with capitals' => ['slug', 'MyTheme', false];
        yield 'package' => ['package', 'acme/my-theme', true];
        yield 'package without vendor' => ['package', 'my-theme', false];
        yield 'PHP namespace' => ['phpNamespace', 'Acme\\MyTheme', true];
        yield 'PHP namespace without vendor' => ['phpNamespace', 'MyTheme', false];
        yield 'PHP namespace of the framework' => ['phpNamespace', 'WebCodingCat\\WpAppFramework', false];
        yield 'PHP namespace inside the framework' => ['phpNamespace', 'WebCodingCat\\WpAppFramework\\Theme', false];
        yield 'PHP namespace next to the framework' => ['phpNamespace', 'WebCodingCat\\WpAppFrameworkDemo', true];
        yield 'namespace' => ['namespace', 'acme-2', true];
        yield 'namespace with a digit first' => ['namespace', '2acme', false];
        yield 'reserved namespace' => ['namespace', 'core', false];
        yield 'namespace of the framework' => ['namespace', 'wp-app-framework', false];
        yield 'prefix' => ['prefix', 'acme', true];
        yield 'prefix with a dash' => ['prefix', 'ac-me', false];
        yield 'theme release' => ['themeRelease', 'acme/my-theme@v1.0.0', true];
        yield 'theme release without tag' => ['themeRelease', 'acme/my-theme', false];
        yield 'kind' => ['kind', 'child-theme', true];
        yield 'unknown kind' => ['kind', 'child', false];
        yield 'yes' => ['yesNo', 'Y', true];
        yield 'maybe' => ['yesNo', 'maybe', false];
        yield 'required' => ['required', ' ', false];
    }

    #[DataProvider('values')]
    public function testRule(string $rule, string $value, bool $valid): void
    {
        /** @var string|null $error Every rule returns the error message or null. */
        $error = Rules::{$rule}($value);

        self::assertSame($valid, $error === null, (string) $error);
    }
}
