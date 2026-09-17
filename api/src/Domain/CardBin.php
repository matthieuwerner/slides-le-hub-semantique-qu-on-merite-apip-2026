<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A Bank Identification Number: the first 6 to 8 digits of a card number.
 *
 * The upper bound of 8 digits is a security control, not a formatting preference. A BIN is
 * the longest card prefix that is safe to log, store and pass around; a 16-digit value
 * arriving here would be a PAN, and this type makes that unrepresentable rather than merely
 * discouraged.
 *
 * PCI-DSS scope is explicitly out of scope for this repository. Every BIN in the fixtures is
 * synthetic and no real primary account number appears anywhere in the project.
 */
final readonly class CardBin
{
    private const string PATTERN = '/^\d{6,8}$/';

    private function __construct(
        public string $digits,
    ) {
    }

    public static function fromString(string $digits): self
    {
        if (1 !== preg_match(self::PATTERN, $digits)) {
            // The rejected value is not echoed back: if a caller mistakenly sent a full PAN,
            // repeating it in an exception message would propagate it into logs and traces.
            throw new \InvalidArgumentException(
                \sprintf('Card BIN must be 6 to 8 digits, got %d characters.', \strlen($digits)),
            );
        }

        return new self($digits);
    }

    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->digits, $prefix);
    }

    public function __toString(): string
    {
        return $this->digits;
    }
}
