<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter\Tests;

use InvalidArgumentException;
use WebCodingCat\WpAppStarter\Prompt;

/**
 * Answers questions from a list by key; a key without answer takes the default. Records the questions and
 * the messages, and fails on an invalid answer like the installer without interaction.
 */
final class FakePrompt implements Prompt
{
    /** @var list<string> */
    private array $asked = [];

    /** @var list<string> */
    private array $said = [];

    /**
     * @param array<string, string> $answers
     */
    public function __construct(private array $answers) {}

    public function ask(string $key, string $question, ?string $default, callable $rule): string
    {
        $this->asked[] = $key;
        $value = $this->answers[$key] ?? $default;
        if ($value === null) {
            throw new InvalidArgumentException(sprintf('No answer for %s.', $key));
        }
        $error = $rule($value);
        if ($error !== null) {
            throw new InvalidArgumentException($key . ': ' . $error);
        }

        return $value;
    }

    public function say(string $message): void
    {
        $this->said[] = $message;
    }

    /**
     * @return list<string>
     */
    public function getAsked(): array
    {
        return $this->asked;
    }

    /**
     * @return list<string>
     */
    public function getSaid(): array
    {
        return $this->said;
    }
}
