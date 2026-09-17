<?php

declare(strict_types=1);

namespace App\Engine\Php;

use App\Domain\RiskInput;

/**
 * The ENSEMBLE profile: a deterministic integer decision-tree ensemble.
 *
 * Mirrors go/risk/ensemble.go bit for bit. See docs/risk-model.md §5.
 *
 * Two constraints drive every unusual thing in this class:
 *
 *  1. **Every intermediate value is pinned to unsigned 32 bits.** PHP has no unsigned 64-bit
 *     integer: arithmetic that passes PHP_INT_MAX silently becomes a float, at which point the
 *     PRNG diverges from Go and the two forests stop being the same forest. Hence the explicit
 *     `& 0xFFFFFFFF` masks. They are not paranoia, they are the contract.
 *
 *  2. **The forest is built once and cached.** Generating ~48 000 integers per request would
 *     make this engine's cost entirely generation cost, and every benchmark comparison would
 *     be measuring the wrong thing.
 *
 * Point 2 has a consequence worth stating out loud: under classic PHP-FPM, the cache dies with
 * the request and this profile is simply not viable. It becomes viable because FrankenPHP's
 * worker mode keeps the process alive between requests. That is a real argument for worker mode
 * that has nothing to do with Go.
 */
final class EnsembleScorer
{
    private const int DEPTH = 6;
    private const int INTERNAL_NODES = (1 << self::DEPTH) - 1; // 63
    private const int LEAF_COUNT = 1 << self::DEPTH;           // 64

    private const int PRNG_SEED = 0x5EED1234;
    private const int MASK32 = 0xFFFFFFFF;

    private const int FEATURE_COUNT = 6;

    /**
     * Quantisation range of each feature. Thresholds are drawn modulo these values, so any
     * change here produces a different forest and invalidates the fixtures.
     *
     * @var list<int>
     */
    private const array FEATURE_SCALE = [
        60000, // 0: amount in major units
        64,    // 1: device transactions in 24h
        4,     // 2: merchant risk tier
        2,     // 3: cross-border flag
        1024,  // 4: hashed card BIN
        1024,  // 5: hashed merchant id
    ];

    /**
     * Process-wide cache. Static rather than an injected service because it must survive
     * across requests in worker mode, and because a stateless service would defeat the point.
     *
     * @var array<int, Forest>
     */
    private static array $forests = [];

    public function __construct(
        private readonly int $treeCount = 256,
    ) {
    }

    /** Materialises the forest ahead of any measurement. */
    public function warmUp(): void
    {
        $this->forest();
    }

    public function score(RiskInput $input): int
    {
        $forest = $this->forest();
        $features = $this->featureVector($input);

        $featureIdx = $forest->featureIdx;
        $threshold = $forest->threshold;
        $leaves = $forest->leaves;
        $treeCount = $forest->treeCount;

        $raw = 0;

        for ($t = 0; $t < $treeCount; ++$t) {
            $base = $t * self::INTERNAL_NODES;
            $node = 0;

            while ($node < self::INTERNAL_NODES) {
                $node = $features[$featureIdx[$base + $node]] <= $threshold[$base + $node]
                    ? 2 * $node + 1
                    : 2 * $node + 2;
            }

            $raw += $leaves[$t * self::LEAF_COUNT + ($node - self::INTERNAL_NODES)];
        }

        // intdiv() truncates toward zero, which is what Go's integer division does.
        // floor() would disagree with Go for negative values and must not be used here.
        $score = intdiv($raw * 50, 100 * $treeCount) + 50;

        return max(0, min(100, $score));
    }

    private function forest(): Forest
    {
        return self::$forests[$this->treeCount] ??= $this->buildForest($this->treeCount);
    }

    /**
     * Generates the forest from the fixed seed.
     *
     * Generated rather than loaded from disk so that the PHP/Go equivalence can be audited by
     * reading twenty lines of PRNG instead of diffing a multi-megabyte fixture.
     */
    private function buildForest(int $treeCount): Forest
    {
        $featureIdx = [];
        $threshold = [];
        $leaves = [];

        $x = self::PRNG_SEED;

        for ($t = 0; $t < $treeCount; ++$t) {
            for ($n = 0; $n < self::INTERNAL_NODES; ++$n) {
                // xorshift32, inlined. Kept inline rather than extracted to a method because
                // it is called ~48 000 times per build and a PHP method call is not free.
                $x ^= ($x << 13) & self::MASK32;
                $x &= self::MASK32;
                $x ^= $x >> 17;
                $x ^= ($x << 5) & self::MASK32;
                $x &= self::MASK32;

                $featureIndex = $x % self::FEATURE_COUNT;
                $featureIdx[] = $featureIndex;

                $x ^= ($x << 13) & self::MASK32;
                $x &= self::MASK32;
                $x ^= $x >> 17;
                $x ^= ($x << 5) & self::MASK32;
                $x &= self::MASK32;

                $threshold[] = $x % self::FEATURE_SCALE[$featureIndex];
            }

            for ($l = 0; $l < self::LEAF_COUNT; ++$l) {
                $x ^= ($x << 13) & self::MASK32;
                $x &= self::MASK32;
                $x ^= $x >> 17;
                $x ^= ($x << 5) & self::MASK32;
                $x &= self::MASK32;

                $leaves[] = ($x % 201) - 100;
            }
        }

        return new Forest($featureIdx, $threshold, $leaves, $treeCount);
    }

    /**
     * @return list<int>
     */
    private function featureVector(RiskInput $input): array
    {
        return [
            $input->amount->majorUnits(),
            $input->deviceTxCount24h,
            $input->merchantRiskTier,
            $input->isCrossBorder() ? 1 : 0,
            self::fnv1a32($input->cardBin->digits) % 1024,
            self::fnv1a32($input->merchantId) % 1024,
        ];
    }

    /**
     * FNV-1a over raw bytes, masked to 32 bits.
     *
     * Chosen purely because it is trivially reimplementable byte-for-byte in Go. A stronger or
     * faster hash would be pointless: reproducibility across two languages is the only property
     * that matters. Pinned against the published FNV test vectors in the unit tests.
     */
    public static function fnv1a32(string $value): int
    {
        $hash = 0x811C9DC5;
        $length = \strlen($value);

        for ($i = 0; $i < $length; ++$i) {
            $hash ^= \ord($value[$i]);
            // 0xFFFFFFFF * 0x01000193 stays well below PHP_INT_MAX, so this cannot promote
            // to float before the mask is applied.
            $hash = ($hash * 0x01000193) & self::MASK32;
        }

        return $hash;
    }
}
