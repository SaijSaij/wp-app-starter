<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter;

/**
 * Asks the questions of the installer: on the console, or from environment variables without interaction.
 */
interface Prompt
{
    /**
     * The answer to a question, checked by the rule; asks again (console) or fails (environment) if it is invalid.
     *
     * @param string                         $key      Name of the answer; the environment variable is WP_APP_STARTER_<KEY>.
     * @param string                         $question The question, without the default.
     * @param string|null                    $default  The answer if none is given; null if an answer is required.
     * @param callable(string): ?string      $rule     Returns the error message of an invalid answer, or null.
     */
    public function ask(string $key, string $question, ?string $default, callable $rule): string;

    /**
     * Prints a line for the user.
     */
    public function say(string $message): void;
}
