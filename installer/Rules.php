<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter;

/**
 * The checks of the answers. Each returns the error message, or null if the value is valid.
 * Slug and namespace follow the rules of the framework's build (Project\ComposerProject).
 */
final class Rules
{
    /** Namespaces the framework reserves (ComposerProject::RESERVED_NAMESPACES). */
    private const RESERVED_NAMESPACES = ['core', 'wp', 'wp-app-framework'];

    public static function kind(string $value): ?string
    {
        return in_array($value, [Answers::THEME, Answers::CHILD_THEME, Answers::PLUGIN], true)
            ? null
            : sprintf('"%s" is no kind; answer theme, child-theme or plugin.', $value);
    }

    public static function slug(string $value): ?string
    {
        return preg_match('/^[a-z0-9_-]+$/', $value) === 1
            ? null
            : sprintf('"%s" is no valid slug: use lowercase letters, digits, "-" and "_".', $value);
    }

    public static function required(string $value): ?string
    {
        return trim($value) === '' ? 'An answer is required.' : null;
    }

    public static function package(string $value): ?string
    {
        // The pattern of Composer's schema for "name".
        return preg_match('{^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$}', $value) === 1
            ? null
            : sprintf('"%s" is no valid package name: vendor/name in lowercase, e.g. acme/my-theme.', $value);
    }

    public static function phpNamespace(string $value): ?string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)+$/', $value) !== 1) {
            return sprintf('"%s" is no valid PHP namespace with a vendor part, e.g. Acme\\MyTheme.', $value);
        }
        if ($value === 'WebCodingCat\\WpAppFramework' || str_starts_with($value, 'WebCodingCat\\WpAppFramework\\')) {
            return 'The namespace WebCodingCat\\WpAppFramework belongs to the framework.';
        }

        return null;
    }

    public static function namespace(string $value): ?string
    {
        if (preg_match('/^[a-z][a-z0-9-]*$/', $value) !== 1) {
            return sprintf('"%s" is no valid namespace: start with a lowercase letter, then use lowercase letters, digits and "-".', $value);
        }
        if (in_array($value, self::RESERVED_NAMESPACES, true)) {
            return sprintf('The namespace "%s" is reserved (%s).', $value, implode(', ', self::RESERVED_NAMESPACES));
        }

        return null;
    }

    public static function prefix(string $value): ?string
    {
        // Tailwind CSS 4 accepts lowercase ASCII letters only.
        return preg_match('/^[a-z]+$/', $value) === 1
            ? null
            : sprintf('"%s" is no valid Tailwind prefix: lowercase letters a-z only.', $value);
    }

    public static function themeRelease(string $value): ?string
    {
        // The format consumer-release.yml of the framework expects for with-theme.
        return preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+@[^@\s]+$/', $value) === 1
            ? null
            : sprintf('"%s" is no theme release: <owner>/<repository>@<tag>, e.g. acme/my-theme@v1.0.0.', $value);
    }

    public static function yesNo(string $value): ?string
    {
        return in_array(strtolower($value), ['yes', 'no', 'y', 'n'], true) ? null : 'Answer yes or no.';
    }
}
