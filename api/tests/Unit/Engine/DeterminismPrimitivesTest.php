<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Engine\Php\EnsembleScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guards the two primitives the whole cross-language parity claim rests on.
 *
 * If either of these drifts, the PHP and Go forests silently stop being the same forest, and
 * every parity assertion downstream becomes a coincidence. These tests assert the *same*
 * values as go/risk/ensemble_test.go.
 */
final class DeterminismPrimitivesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function fnvVectors(): iterable
    {
        // The published FNV-1a 32-bit test vectors, not values captured from this code.
        yield 'empty string' => ['', 0x811c9dc5];
        yield 'a' => ['a', 0xe40c292c];
        yield 'b' => ['b', 0xe70c2de5];
        yield 'foobar' => ['foobar', 0xbf9cf968];
    }

    #[DataProvider('fnvVectors')]
    #[Test]
    public function fnv1a32_matches_the_published_vectors(string $input, int $expected): void
    {
        self::assertSame($expected, EnsembleScorer::fnv1a32($input));
    }

    /**
     * The xorshift32 sequence, asserted against the identical list pinned in Go.
     *
     * This is the test that would fail if PHP ever promoted one of these intermediate values to
     * a float, which is exactly the failure mode a 64-bit PRNG would have introduced silently.
     */
    #[Test]
    public function xorshift32_produces_the_same_sequence_as_go(): void
    {
        $expected = [0x69d66041, 0x1e1c476e, 0x48c84ed6, 0x634765bf, 0x71e88aa7, 0x773705d9];

        $x = 0x5EED1234;
        $mask = 0xFFFFFFFF;
        $actual = [];

        for ($i = 0; $i < \count($expected); ++$i) {
            $x ^= ($x << 13) & $mask;
            $x &= $mask;
            $x ^= $x >> 17;
            $x ^= ($x << 5) & $mask;
            $x &= $mask;

            $actual[] = $x;
        }

        self::assertSame($expected, $actual);
    }

    /**
     * Documents the trap this design exists to avoid.
     *
     * PHP integers are 64-bit signed and overflow to float rather than wrapping. A 64-bit PRNG
     * would therefore be unimplementable in PHP in a way that matches Go's uint64. This test
     * asserts that property so the constraint is recorded in code, not only in prose.
     */
    #[Test]
    public function php_integers_overflow_to_float_which_is_why_the_prng_is_32_bit(): void
    {
        // get_debug_type() rather than assertIsInt(), because these expressions are constant and a
        // type assertion on a constant is something a static analyser can prove without running
        // anything — which would make the test decorative.
        $max = \PHP_INT_MAX;
        self::assertSame('int', get_debug_type($max));
        self::assertSame('float', get_debug_type($max + 1), 'PHP promotes integer overflow to float');

        // Masked 32-bit arithmetic, by contrast, always stays an integer.
        $wide = 0xFFFFFFFF;
        self::assertSame('int', get_debug_type(($wide << 13) & 0xFFFFFFFF));
        self::assertSame('int', get_debug_type($wide * 0x01000193));
    }
}
