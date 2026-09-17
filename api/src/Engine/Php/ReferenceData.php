<?php

declare(strict_types=1);

namespace App\Engine\Php;

/**
 * The scoring reference tables.
 *
 * These tables are duplicated in go/risk/rules.go. That duplication is deliberate, and it is
 * the one place in this repository where the same knowledge lives twice.
 *
 * The alternatives were worse:
 *   - loading a shared JSON file would put I/O and parsing inside a function whose cost we are
 *     trying to measure, and the Go extension would need to locate that file from inside a PHP
 *     process;
 *   - generating both from one source would add a codegen step whose only output is four small
 *     lists.
 *
 * The mitigation is that fixtures/risk-cases.json exercises every entry below, so a divergence
 * between the two languages fails "make parity-test" rather than quietly changing decisions in
 * production. See docs/limitations.md.
 */
final class ReferenceData
{
    /** Per-authorization ceiling: 50 000.00 in any supported currency. */
    public const int MAX_AMOUNT_MINOR = 5_000_000;

    /**
     * Currencies we can settle. A currency outside this list is a hard decline rather than a
     * risk factor, because the issue is not risk: we cannot process it at all.
     *
     * @var list<string>
     */
    public const array SUPPORTED_CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF', 'SEK'];

    /** @var list<string> */
    public const array SANCTIONED_ISSUER_COUNTRIES = ['IR', 'KP', 'SY', 'CU'];

    /**
     * The currency we expect for a given acquirer country. A country absent from this map has
     * no expectation, so the mismatch rule cannot fire for it.
     *
     * @var array<string, string>
     */
    public const array EXPECTED_CURRENCY = [
        'FR' => 'EUR', 'DE' => 'EUR', 'ES' => 'EUR', 'IT' => 'EUR', 'NL' => 'EUR',
        'BE' => 'EUR', 'PT' => 'EUR', 'IE' => 'EUR', 'AT' => 'EUR', 'FI' => 'EUR',
        'US' => 'USD',
        'GB' => 'GBP',
        'CH' => 'CHF',
        'SE' => 'SEK',
    ];

    /**
     * Synthetic high-risk BIN prefixes. No real issuer range is named here.
     *
     * @var list<string>
     */
    public const array HIGH_RISK_BIN_PREFIXES = ['521234', '601199', '492910', '551002'];
}
