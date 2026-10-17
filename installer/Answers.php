<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter;

/**
 * Everything the questions of the installer found out about the new project.
 */
final class Answers
{
    public const THEME = 'theme';
    public const CHILD_THEME = 'child-theme';
    public const PLUGIN = 'plugin';

    /**
     * @param string      $kind         self::THEME, self::CHILD_THEME or self::PLUGIN.
     * @param string      $slug         Directory name of the project.
     * @param string      $name         Theme Name or Plugin Name.
     * @param string      $package      Composer package name, vendor/name.
     * @param string      $description  One sentence.
     * @param string      $author       Author in the header.
     * @param string      $phpNamespace PHP namespace of src/, without leading or trailing backslash.
     * @param string      $namespace    Namespace of the framework (extra.wp-app-framework.namespace).
     * @param string|null $parent       Slug of the parent theme (child themes only).
     * @param string|null $prefix       Tailwind prefix (plugins only).
     * @param string|null $withTheme    Theme release for the release test, owner/repository@tag (child themes, plugins).
     * @param bool        $example      With the example block.
     * @param bool        $quality      With PHPStan, PHP-CS-Fixer and ESLint.
     * @param bool        $ci           With the GitHub release workflow.
     * @param bool        $e2e          With browser tests for wp-test-env.
     */
    public function __construct(
        private string $kind,
        private string $slug,
        private string $name,
        private string $package,
        private string $description,
        private string $author,
        private string $phpNamespace,
        private string $namespace,
        private ?string $parent,
        private ?string $prefix,
        private ?string $withTheme,
        private bool $example,
        private bool $quality,
        private bool $ci,
        private bool $e2e,
    ) {}

    public function getKind(): string
    {
        return $this->kind;
    }

    public function isTheme(): bool
    {
        return $this->kind === self::THEME;
    }

    public function isChildTheme(): bool
    {
        return $this->kind === self::CHILD_THEME;
    }

    public function isPlugin(): bool
    {
        return $this->kind === self::PLUGIN;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getPhpNamespace(): string
    {
        return $this->phpNamespace;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getWithTheme(): ?string
    {
        return $this->withTheme;
    }

    public function hasExample(): bool
    {
        return $this->example;
    }

    public function hasQuality(): bool
    {
        return $this->quality;
    }

    public function hasCi(): bool
    {
        return $this->ci;
    }

    public function hasE2e(): bool
    {
        return $this->e2e;
    }

    /**
     * The file that boots the framework: functions.php, or {slug}.php of a plugin.
     */
    public function getBootFile(): string
    {
        return $this->isPlugin() ? $this->slug . '.php' : 'functions.php';
    }
}
