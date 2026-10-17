<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter\Tests;

use Composer\IO\BufferIO;
use Composer\IO\NullIO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebCodingCat\WpAppStarter\ComposerPrompt;
use WebCodingCat\WpAppStarter\Rules;

final class ComposerPromptTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('WP_APP_STARTER_PHP_NAMESPACE');
    }

    public function testInteractiveAsksAgainAfterAnInvalidAnswerAndTakesTheDefault(): void
    {
        $io = new BufferIO();
        $io->setUserInputs(['Not a slug', 'my-theme', '']);
        $prompt = new ComposerPrompt($io);

        self::assertSame('my-theme', $prompt->ask('parent', 'Slug of the parent theme', null, Rules::slug(...)));
        self::assertSame('theme', $prompt->ask('kind', 'Kind', 'theme', Rules::kind(...)));
        self::assertStringContainsString('"Not a slug" is no valid slug', $io->getOutput());
    }

    public function testWithoutInteractionTheAnswersComeFromTheEnvironment(): void
    {
        // NullIO is not interactive, like composer create-project --no-interaction.
        $io = new NullIO();
        putenv('WP_APP_STARTER_PHP_NAMESPACE=Acme\\Shop');
        $prompt = new ComposerPrompt($io);

        self::assertSame('Acme\\Shop', $prompt->ask('php-namespace', 'PHP namespace', 'Acme\\Other', Rules::phpNamespace(...)));
        self::assertSame('theme', $prompt->ask('kind', 'Kind', 'theme', Rules::kind(...)));
    }

    public function testWithoutInteractionARequiredAnswerMustBeSet(): void
    {
        $io = new NullIO();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WP_APP_STARTER_NAMESPACE is required without interaction: Namespace');
        (new ComposerPrompt($io))->ask('namespace', 'Namespace', null, Rules::namespace(...));
    }

    public function testWithoutInteractionAnInvalidAnswerFails(): void
    {
        $io = new NullIO();
        putenv('WP_APP_STARTER_PHP_NAMESPACE=Shop');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WP_APP_STARTER_PHP_NAMESPACE: "Shop" is no valid PHP namespace');
        (new ComposerPrompt($io))->ask('php-namespace', 'PHP namespace', null, Rules::phpNamespace(...));
    }
}
