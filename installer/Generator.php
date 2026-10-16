<?php

declare(strict_types=1);

namespace Saij\WpAppStarter;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Writes the files of the new project from the parts in skeleton/: common/, the kind (theme/, child-theme/,
 * plugin/) and the chosen options (example/, quality/, ci/, e2e/).
 *
 * In the files of the parts:
 * - placeholders such as __SLUG__ are replaced (see replacements());
 * - lines `@@if <flag>`, `@@else`, `@@endif` keep or drop the lines between them (flags: see flags());
 * - a leading `_` of a file or directory name becomes `.` (`_gitignore` → `.gitignore`), so the parts do not act
 *   on the repository of the starter.
 *
 * composer.json and package.json are written as data, not from templates.
 */
final class Generator
{
    public const PARTS = ['common', 'example', 'quality', 'ci', 'e2e'];

    /**
     * @param string $frameworkConstraint Version constraint of saij/wp-app-framework, as the starter requires it.
     * @param string $frameworkRef        Ref of wp-app-framework for consumer-release.yml, e.g. v0.16.0 or main.
     */
    public function __construct(
        private Answers $answers,
        private string $frameworkConstraint,
        private string $frameworkRef,
    ) {}

    /**
     * The files of the project, by path relative to its directory, with their contents. Renders everything before
     * anything is written, so an error in a part leaves the directory untouched.
     *
     * @return array<string, string>
     */
    public function build(string $skeleton): array
    {
        $files = [];
        foreach ($this->parts() as $part) {
            $directory = $skeleton . '/' . $part;
            if (!is_dir($directory)) {
                throw new RuntimeException(sprintf('The part %s of the starter is missing.', $directory));
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($directory) + 1);
                $path = $this->targetPath($relative);
                if (isset($files[$path])) {
                    throw new RuntimeException(sprintf('Two parts of the starter write %s.', $path));
                }
                $files[$path] = $this->render((string) file_get_contents($file->getPathname()), $part . '/' . $relative);
            }
        }
        if (!$this->answers->hasExample()) {
            $files['src/.gitkeep'] = '';
        }
        $files['composer.json'] = self::json($this->composerJson());
        $files['package.json'] = self::json($this->packageJson(), "\t");
        ksort($files);

        return $files;
    }

    /**
     * Writes the files of build() into the target directory.
     *
     * @param array<string, string> $files
     */
    public static function write(array $files, string $target): void
    {
        foreach ($files as $path => $content) {
            self::writeFile($target . '/' . $path, $content);
        }
    }

    /**
     * @return list<string>
     */
    private function parts(): array
    {
        $parts = ['common', $this->answers->getKind()];
        foreach (['example' => $this->answers->hasExample(), 'quality' => $this->answers->hasQuality(), 'ci' => $this->answers->hasCi(), 'e2e' => $this->answers->hasE2e()] as $part => $chosen) {
            if ($chosen) {
                $parts[] = $part;
            }
        }

        return $parts;
    }

    private function targetPath(string $relative): string
    {
        // Placeholders first: __SLUG__.php starts with "_" as well.
        $relative = strtr(str_replace('\\', '/', $relative), ['__SLUG__' => $this->answers->getSlug()]);
        $segments = array_map(
            static fn(string $segment): string => str_starts_with($segment, '_') ? '.' . substr($segment, 1) : $segment,
            explode('/', $relative),
        );

        return implode('/', $segments);
    }

    /**
     * Applies the @@if blocks and the placeholders.
     */
    public function render(string $content, string $source = 'template'): string
    {
        $flags = $this->flags();
        $lines = preg_split('/(?<=\n)/', $content) ?: [];
        $output = '';
        // One entry per open @@if: whether its lines are kept, and whether the lines around it are.
        $stack = [];
        $keep = true;
        foreach ($lines as $number => $line) {
            if (preg_match('/^\s*@@if (!?)([a-z0-9-]+)\s*$/', $line, $match) === 1) {
                if (!array_key_exists($match[2], $flags)) {
                    throw new RuntimeException(sprintf('%s, line %d: unknown flag "%s".', $source, $number + 1, $match[2]));
                }
                $stack[] = [$keep, $flags[$match[2]] !== ($match[1] === '!')];
                $keep = $keep && end($stack)[1];
                continue;
            }
            if (preg_match('/^\s*@@else\s*$/', $line) === 1) {
                if ($stack === []) {
                    throw new RuntimeException(sprintf('%s, line %d: @@else without @@if.', $source, $number + 1));
                }
                [$outer, $condition] = array_pop($stack);
                $stack[] = [$outer, !$condition];
                $keep = $outer && !$condition;
                continue;
            }
            if (preg_match('/^\s*@@endif\s*$/', $line) === 1) {
                if ($stack === []) {
                    throw new RuntimeException(sprintf('%s, line %d: @@endif without @@if.', $source, $number + 1));
                }
                [$keep] = array_pop($stack);
                continue;
            }
            if ($keep) {
                $output .= $line;
            }
        }
        if ($stack !== []) {
            throw new RuntimeException(sprintf('%s: @@if without @@endif.', $source));
        }
        $output = strtr($output, $this->replacements());
        // A misspelt placeholder; the magic constants of PHP are no placeholders.
        if (preg_match('/__(?!(?:DIR|FILE|LINE|CLASS|FUNCTION|METHOD|NAMESPACE|TRAIT|PROPERTY)__)[A-Z][A-Z_]*__/', $output, $match) === 1) {
            throw new RuntimeException(sprintf('%s: unknown placeholder %s.', $source, $match[0]));
        }

        return $output;
    }

    /**
     * @return array<string, bool>
     */
    private function flags(): array
    {
        $a = $this->answers;

        return [
            'theme' => $a->isTheme(),
            'child-theme' => $a->isChildTheme(),
            'plugin' => $a->isPlugin(),
            'example' => $a->hasExample(),
            'quality' => $a->hasQuality(),
            'ci' => $a->hasCi(),
            'e2e' => $a->hasE2e(),
            'with-theme' => $a->getWithTheme() !== null,
            'description' => $a->getDescription() !== '',
            'author' => $a->getAuthor() !== '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function replacements(): array
    {
        $a = $this->answers;

        return [
            '__SLUG__' => $a->getSlug(),
            '__NAME__' => $a->getName(),
            '__PACKAGE__' => $a->getPackage(),
            '__DESCRIPTION__' => $a->getDescription(),
            '__AUTHOR__' => $a->getAuthor(),
            '__PHP_NAMESPACE__' => $a->getPhpNamespace(),
            '__WP_NAMESPACE__' => $a->getNamespace(),
            '__PARENT__' => (string) $a->getParent(),
            '__PREFIX__' => (string) $a->getPrefix(),
            // Tailwind classes of a plugin carry its prefix as a variant (pdt:p-4); themes have none.
            '__TW__' => $a->getPrefix() === null ? '' : $a->getPrefix() . ':',
            '__BOOT_FILE__' => $a->getBootFile(),
            '__KIND_DIRECTORY__' => $a->isPlugin() ? 'plugins' : 'themes',
            // The file whose Version header the release tag must equal.
            '__VERSION_FILE__' => $a->isPlugin() ? $a->getBootFile() : 'style.css',
            '__WITH_THEME__' => (string) $a->getWithTheme(),
            '__FRAMEWORK_REF__' => $this->frameworkRef,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function composerJson(): array
    {
        $a = $this->answers;
        $vendor = $a->getPhpNamespace() . '\\Vendor\\';
        $json = [
            'name' => $a->getPackage(),
            'description' => $a->getDescription(),
            'type' => $a->isPlugin() ? 'wordpress-plugin' : 'wordpress-theme',
            'license' => 'GPL-2.0-or-later',
        ];
        if ($a->getDescription() === '') {
            unset($json['description']);
        }
        if ($a->getAuthor() !== '') {
            $json['authors'] = [['name' => $a->getAuthor()]];
        }
        $json['require'] = ['php' => '>=8.5', 'saij/wp-app-framework' => $this->frameworkConstraint];
        if ($a->hasQuality()) {
            $json['require-dev'] = [
                'friendsofphp/php-cs-fixer' => '^3.95',
                'php-stubs/wordpress-stubs' => '^7.1',
                'phpstan/phpstan' => '^2.3',
                'szepeviktor/phpstan-wordpress' => '^2.0',
            ];
        }
        $json['autoload'] = ['psr-4' => [$a->getPhpNamespace() . '\\' => 'src/']];
        $scripts = ['build-manifest' => 'wp-app-framework build'];
        $descriptions = ['build-manifest' => 'Builds and checks the manifest (writes nothing).'];
        if ($a->hasQuality()) {
            $scripts['lint'] = ['phpstan analyse --no-progress --memory-limit=1G', 'php-cs-fixer check --diff'];
            $scripts['fix'] = 'php-cs-fixer fix';
            $descriptions['lint'] = 'PHPStan and PHP-CS-Fixer.';
            $descriptions['fix'] = 'Fixes the code style.';
        }
        $scripts['release'] = 'wp-app-framework release';
        $descriptions['release'] = 'Builds the release ZIP into dist/.';
        $json['scripts'] = $scripts;
        $json['scripts-descriptions'] = $descriptions;
        $json['extra'] = [
            'wp-app-framework' => ['namespace' => $a->getNamespace()],
            'strauss' => [
                'target_directory' => 'vendor-prefixed',
                'namespace_prefix' => $vendor,
                'classmap_prefix' => str_replace('\\', '_', $vendor),
                'include_root_autoload' => true,
                'update_call_sites' => ['src', $a->getBootFile()],
                'delete_vendor_packages' => false,
            ],
        ];
        $json['config'] = ['platform' => ['php' => '8.5'], 'sort-packages' => true];

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function packageJson(): array
    {
        $a = $this->answers;
        // src/ and assets/ may hold no scripts yet; ESLint and Prettier fail on such paths without the option.
        $paths = 'src assets webpack.config.js eslint.config.cjs' . ($a->hasE2e() ? ' tests' : '');
        $scripts = ['build' => 'wp-scripts build', 'start' => 'wp-scripts start'];
        if ($a->hasQuality()) {
            $scripts['lint'] = 'wp-scripts lint-js --no-error-on-unmatched-pattern ' . $paths;
            $scripts['format'] = 'wp-scripts format --no-error-on-unmatched-pattern ' . $paths;
        }
        if ($a->hasE2e()) {
            $scripts['test:e2e'] = 'playwright test --config tests/e2e/playwright.config.js';
        }
        $dependencies = [
            '@saij/wp-app-framework' => 'file:vendor/saij/wp-app-framework',
            '@tailwindcss/webpack' => '^4.3.3',
            '@wordpress/scripts' => '^36.1.0',
            'tailwindcss' => '^4.3.3',
        ];
        if ($a->hasE2e()) {
            // Exact: wp-test-env runs the tests in the Playwright image of this version.
            $dependencies['@playwright/test'] = '1.64.0';
            $dependencies['@wordpress/e2e-test-utils-playwright'] = '^3.0.0';
        }
        ksort($dependencies);

        return [
            'name' => $a->getSlug(),
            'private' => true,
            'license' => 'GPL-2.0-or-later',
            'engines' => ['node' => '>=24'],
            'scripts' => $scripts,
            'devDependencies' => $dependencies,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function json(array $data, string $indent = '    '): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if ($indent !== '    ') {
            $json = (string) preg_replace_callback('/^( {4})+/m', static fn(array $m): string => str_repeat($indent, intdiv(strlen($m[0]), 4)), $json);
        }

        return $json . "\n";
    }

    private static function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Cannot create the directory %s.', $directory));
        }
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException(sprintf('Cannot write %s.', $path));
        }
    }
}
