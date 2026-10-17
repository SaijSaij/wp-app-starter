<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use RuntimeException;

/**
 * Turns the starter into a new project after `composer create-project webcodingcat/wp-app-starter <directory>`
 * (script post-create-project-cmd): asks the questions, writes the project, removes the starter's own files,
 * installs the dependencies of the project and, if Node 24 or newer is available, builds its scripts.
 *
 * Composer has installed the starter's requirements at that point and does not read composer.json again
 * (Composer 2.10, CreateProjectCommand), so the installer runs `composer update` itself.
 */
final class Installer
{
    /** Files and directories of the starter that the project does not keep. */
    private const STARTER_FILES = ['installer', 'skeleton', 'tests', '.github', '.gitattributes', 'composer.lock', 'README.md', 'CLAUDE.md'];

    public static function run(Event $event): void
    {
        $io = $event->getIO();
        $root = (string) getcwd();
        $composer = $event->getComposer();
        $link = $composer->getPackage()->getRequires()['webcodingcat/wp-app-framework'] ?? null;
        if ($link === null) {
            throw new RuntimeException('The composer.json of the starter does not require webcodingcat/wp-app-framework.');
        }
        $installed = $composer->getRepositoryManager()->getLocalRepository()->findPackage('webcodingcat/wp-app-framework', '*');
        if ($installed === null) {
            throw new RuntimeException('webcodingcat/wp-app-framework is not installed; run create-project without --no-install.');
        }

        $io->write('<info>Create a theme, child theme or plugin with wp-app-framework</info>');
        $answers = (new Questions(new ComposerPrompt($io)))->ask(basename($root));
        $generator = new Generator($answers, $link->getPrettyConstraint(), self::frameworkRef($installed->getPrettyVersion(), $installed->isDev()));

        // Everything is rendered before the starter's own files go, so an error leaves the directory as it was.
        $files = $generator->build($root . '/skeleton');
        foreach (self::STARTER_FILES as $file) {
            self::remove($root . '/' . $file);
        }
        Generator::write($files, $root);
        // Composer keeps the class name of the autoloader (ComposerAutoloaderInit<suffix>) from an existing
        // vendor/autoload.php; the first install took it from the lock file of the starter, the same for every
        // project. Without the file, it comes from the project's own lock file, so two projects can run side by
        // side in development mode (Composer 2.10, AutoloadGenerator::dump()).
        self::remove($root . '/vendor/autoload.php');

        self::composerUpdate($io);
        self::build($io);
        self::nextSteps($io, $answers);
    }

    /**
     * The ref of consumer-release.yml: the tag of the installed framework, main for a development version.
     */
    public static function frameworkRef(string $version, bool $dev): string
    {
        if ($dev) {
            return 'main';
        }

        return str_starts_with($version, 'v') ? $version : 'v' . $version;
    }

    private static function composerUpdate(IOInterface $io): void
    {
        $composer = getenv('COMPOSER_BINARY');
        if ($composer === false || $composer === '') {
            throw new RuntimeException('COMPOSER_BINARY is not set; run the starter through composer create-project.');
        }
        $io->write('<info>Installing the dependencies of the project (composer update)</info>');
        self::exec([PHP_BINARY, $composer, 'update', '--no-interaction'], 'composer update');
    }

    private static function build(IOInterface $io): void
    {
        $version = self::nodeMajorVersion();
        if ($version === null || $version < 24) {
            $io->write(sprintf(
                '<comment>%s, so the scripts and styles are not built yet. Install Node 24 or newer and run npm install and npm run build in the project.</comment>',
                $version === null ? 'Node is not available' : sprintf('Node %d is older than 24', $version),
            ));

            return;
        }
        $io->write('<info>Building the scripts and styles (npm install, npm run build)</info>');
        self::exec(['npm', 'install', '--no-audit', '--no-fund'], 'npm install');
        self::exec(['npm', 'run', 'build'], 'npm run build');
    }

    private static function nodeMajorVersion(): ?int
    {
        $output = [];
        $code = 1;
        @exec('node --version 2>&1', $output, $code);
        if ($code !== 0 || preg_match('/^v(\d+)\./', $output[0] ?? '', $match) !== 1) {
            return null;
        }

        return (int) $match[1];
    }

    private static function nextSteps(IOInterface $io, Answers $answers): void
    {
        $io->write('');
        $io->write(sprintf('<info>%s is ready.</info> README.md lists the commands; the next steps:', $answers->getName()));
        $io->write('  - git init and a first commit: the release build (composer release) takes the files Git knows.');
        $io->write(sprintf(
            '  - Put the directory into wp-content/%s of a WordPress 7.1 with PHP 8.5 and activate it.',
            $answers->isPlugin() ? 'plugins' : 'themes',
        ));
        if ($answers->isChildTheme()) {
            $io->write(sprintf('  - The parent theme %s must be installed next to it.', (string) $answers->getParent()));
        }
        $io->write('  - npm run start rebuilds the scripts and styles while you work.');
    }

    /**
     * @param list<string> $command
     */
    private static function exec(array $command, string $label): void
    {
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Cannot run %s.', $label));
        }
        $code = proc_close($process);
        if ($code !== 0) {
            throw new RuntimeException(sprintf('%s failed with exit code %d. The project files are written; fix the problem and run %s again in the project.', $label, $code, $label));
        }
    }

    private static function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::remove($path . '/' . $entry);
            }
        }
        rmdir($path);
    }
}
