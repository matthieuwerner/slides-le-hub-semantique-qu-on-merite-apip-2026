<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A monetary amount held in minor units.
 *
 * There is no float anywhere in this class and there must never be one. 42069 EUR-cents is
 * an integer; 420.69 is a value that cannot be represented exactly in binary floating point,
 * and a risk engine that compares an amount against a threshold with float arithmetic will
 * eventually disagree with itself.
 *
 * The integer-only rule is also what makes the PHP and Go implementations provably identical:
 * see docs/risk-model.md §1.1.
 */
final readonly class Money
{
    private function __construct(
        public int $minorUnits,
        public Currency $currency,
    ) {
    }

    public static function fromMinorUnits(int $minorUnits, Currency $currency): self
    {
        return new self($minorUnits, $currency);
    }

    /**
     * The amount truncated to major units.
     *
     * Truncating (not rounding) is intentional: this is only ever used to quantise a feature
     * for the scoring model, and it must match Go's integer division exactly.
     */
    public function majorUnits(): int
    {
        return intdiv($this->minorUnits, 100);
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function __toString(): string
    {
        return \sprintf('%d %s', $this->minorUnits, $this->currency->code);
    }
}
