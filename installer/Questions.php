<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter;

use InvalidArgumentException;

/**
 * The questions of the installer, in order. The slug is not asked: it is the directory create-project created.
 */
final class Questions
{
    public function __construct(private Prompt $prompt) {}

    public function ask(string $slug): Answers
    {
        $error = Rules::slug($slug);
        if ($error !== null) {
            throw new InvalidArgumentException(sprintf('The directory name is the slug of the project. %s Choose another directory for composer create-project.', $error));
        }
        $this->prompt->say(sprintf('Slug (the directory name): <comment>%s</comment>', $slug));

        $kind = $this->prompt->ask('kind', 'Kind of project: theme, child-theme or plugin', Answers::THEME, Rules::kind(...));
        $parent = $kind === Answers::CHILD_THEME
            ? $this->prompt->ask('parent', 'Slug of the parent theme', null, static fn(string $value): ?string => Rules::slug($value) ?? ($value === $slug ? 'The parent theme needs another slug than the child theme.' : null))
            : null;
        $name = $this->prompt->ask('name', $kind === Answers::PLUGIN ? 'Plugin name' : 'Theme name', self::title($slug), Rules::required(...));
        $package = $this->prompt->ask('package', 'Composer package name (vendor/name)', null, Rules::package(...));
        $description = $this->prompt->ask('description', 'Description (one sentence)', '', static fn(string $value): ?string => null);
        $author = $this->prompt->ask('author', 'Author', '', static fn(string $value): ?string => null);
        $phpNamespace = $this->prompt->ask('php-namespace', 'PHP namespace of src/', self::phpNamespaceOf($package), Rules::phpNamespace(...));
        $this->prompt->say('The namespace names your blocks, REST routes, settings, post types and tables; content refers to it, so choose it once.');
        $namespace = $this->prompt->ask('namespace', 'Namespace (e.g. acme for the blocks acme/…)', null, Rules::namespace(...));
        $prefix = null;
        if ($kind === Answers::PLUGIN) {
            $letters = preg_replace('/[^a-z]/', '', $namespace) ?? '';
            $prefix = $this->prompt->ask('prefix', 'Tailwind prefix of the plugin\'s classes', $letters === '' ? null : $letters, Rules::prefix(...));
        }

        $example = $this->yes('example', 'Add an example block (teaser)?');
        $quality = $this->yes('quality', 'Add PHPStan, PHP-CS-Fixer and ESLint?');
        $ci = $this->yes('ci', 'Add the GitHub release workflow?');
        $withTheme = null;
        if ($ci && $kind === Answers::CHILD_THEME) {
            $withTheme = $this->prompt->ask('with-theme', 'Release of the parent theme for the release test (<owner>/<repository>@<tag>)', null, Rules::themeRelease(...));
        } elseif ($ci && $kind === Answers::PLUGIN) {
            $withTheme = $this->prompt->ask('with-theme', 'Theme release for the release test (<owner>/<repository>@<tag>; empty: the default theme of WordPress)', '', static fn(string $value): ?string => $value === '' ? null : Rules::themeRelease($value));
            $withTheme = $withTheme === '' ? null : $withTheme;
        }
        $e2e = $this->yes('e2e', 'Add browser tests for wp-test-env?');

        return new Answers($kind, $slug, $name, $package, $description, $author, $phpNamespace, $namespace, $parent, $prefix, $withTheme, $example, $quality, $ci, $e2e);
    }

    private function yes(string $key, string $question): bool
    {
        return in_array(strtolower($this->prompt->ask($key, $question . ' (yes/no)', 'yes', Rules::yesNo(...))), ['yes', 'y'], true);
    }

    /**
     * "my-theme" → "My Theme".
     */
    private static function title(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    /**
     * "acme/my-theme" → "Acme\MyTheme".
     */
    private static function phpNamespaceOf(string $package): string
    {
        $parts = array_map(
            static fn(string $part): string => str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $part))),
            explode('/', $package),
        );

        return implode('\\', $parts);
    }
}
