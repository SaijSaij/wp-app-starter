<?php

declare(strict_types=1);

namespace Saij\WpAppStarter;

use Composer\IO\IOInterface;
use InvalidArgumentException;

/**
 * Asks on Composer's console. Without interaction (composer create-project --no-interaction), the answers come
 * from the environment variables WP_APP_STARTER_<KEY>; a missing required answer or an invalid one is an error.
 */
final class ComposerPrompt implements Prompt
{
    public function __construct(private IOInterface $io) {}

    public function ask(string $key, string $question, ?string $default, callable $rule): string
    {
        if (!$this->io->isInteractive()) {
            $variable = 'WP_APP_STARTER_' . strtoupper(str_replace('-', '_', $key));
            $value = getenv($variable);
            $value = $value === false ? $default : $value;
            if ($value === null) {
                throw new InvalidArgumentException(sprintf('%s is required without interaction: %s', $variable, $question));
            }
            $error = $rule($value);
            if ($error !== null) {
                throw new InvalidArgumentException(sprintf('%s: %s', $variable, $error));
            }

            return $value;
        }

        $label = sprintf('<info>%s</info>%s: ', $question, $default === null ? '' : sprintf(' [<comment>%s</comment>]', $default));

        $answer = $this->io->askAndValidate($label, static function (mixed $answer) use ($default, $rule): string {
            $value = is_string($answer) ? trim($answer) : '';
            if ($value === '' && $default !== null) {
                $value = $default;
            }
            $error = $rule($value);
            if ($error !== null) {
                throw new InvalidArgumentException($error);
            }

            return $value;
        });

        return is_string($answer) ? $answer : throw new InvalidArgumentException(sprintf('No answer to "%s".', $question));
    }

    public function say(string $message): void
    {
        $this->io->write($message);
    }
}
