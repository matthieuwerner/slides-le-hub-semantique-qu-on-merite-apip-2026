<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * An ISO 4217 alpha-3 currency code.
 *
 * Deliberately *not* an enum of the currencies we accept. Those are two different questions,
 * and conflating them produces the wrong HTTP semantics:
 *
 *   - "XX1" is not a currency at all              -> 422, the request is malformed
 *   - "JPY" is a currency we do not settle         -> 200, with a declined decision
 *
 * A closed enum would turn the second case into the first, and a payment client cannot tell
 * "you sent me garbage" apart from "I refuse this business" if both are a 422.
 */
final readonly class Currency
{
    private const string PATTERN = '/^[A-Z]{3}$/';

    private function __construct(
        public string $code,
    ) {
    }

    public static function fromString(string $code): self
    {
        if (1 !== preg_match(self::PATTERN, $code)) {
            throw new \InvalidArgumentException(
                \sprintf('Currency must be an ISO 4217 alpha-3 code, got "%s".', $code),
            );
        }

        return new self($code);
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
