<?php

declare(strict_types=1);

namespace App\Lab\Command;

use App\Domain\RiskEngineUnavailable;
use App\Domain\RiskInput;
use App\Domain\RiskProfile;
use App\Engine\EngineRegistry;
use App\Lab\FixtureCases;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Level 1 benchmark: the cost of crossing each boundary, with nothing else in the way.
 *
 * No HTTP server, no API Platform, no JSON-LD, no validation. Just `RiskEngine::assess()` called in
 * a loop from a PHP CLI process that has all configured engines available.
 *
 * This is the measurement that answers the actual question of the talk. The end-to-end numbers
 * (Level 2, `make bench-macro`) answer a different and equally important one: whether any of this is
 * visible to a client. Reporting only one of the two is how benchmarks mislead — the first without
 * the second overstates the stakes, the second without the first hides the mechanism.
 *
 * ## On measurement honesty
 *
 * Timing individual calls with `hrtime()` costs something, and on the fastest path here that cost is
 * a real fraction of the measurement. So the command calibrates `hrtime()` against itself and prints
 * the result alongside the numbers rather than quietly subtracting it. If the calibration figure is
 * a large share of a reported p50, that p50 deserves suspicion, and the reader is given what they
 * need to notice.
 */
#[AsCommand(
    name: 'lab:bench:micro',
    description: 'Measure the cost of each engine boundary, isolated from HTTP and API Platform',
)]
final class MicroBenchCommand extends Command
{
    public function __construct(
        private readonly EngineRegistry $engines,
        private readonly int $treeCount,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED,
                'Measured iterations per engine and profile', '2000')
            ->addOption('warmup', 'w', InputOption::VALUE_REQUIRED,
                'Unmeasured warm-up iterations', '500')
            // Named --risk-profile, not --profile: Symfony's framework bundle already registers a
            // global --profile option, and re-declaring it makes the command fail to construct.
            ->addOption('risk-profile', null, InputOption::VALUE_REQUIRED,
                'Only measure one scoring profile (RULES or ENSEMBLE)')
            ->addOption('json', null, InputOption::VALUE_REQUIRED,
                'Also write raw results as JSON to this path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Console options are mixed. Narrowed rather than cast, so a typo becomes a clear failure
        // instead of silently benchmarking zero iterations.
        $rawIterations = $input->getOption('iterations');
        $rawWarmup = $input->getOption('warmup');

        $iterations = max(100, \is_numeric($rawIterations) ? (int) $rawIterations : 2000);
        $warmup = max(10, \is_numeric($rawWarmup) ? (int) $rawWarmup : 500);

        $profiles = RiskProfile::cases();
        if (null !== $only = $input->getOption('risk-profile')) {
            \assert(\is_string($only));
            $profile = RiskProfile::tryFrom(strtoupper($only));

            if (null === $profile) {
                $io->error(\sprintf('Unknown profile "%s". Use RULES or ENSEMBLE.', $only));

                return Command::FAILURE;
            }
            $profiles = [$profile];
        }

        $engines = $this->engines->available();

        if (count(\App\Engine\EngineName::cases()) !== count($engines)) {
            $io->error('All configured engines are required for a comparative campaign.');

            return Command::FAILURE;
        }

        $io->title('Level 1 — boundary cost, isolated');

        $timerOverheadNs = $this->calibrateTimer();

        $io->definitionList(
            ['iterations' => (string) $iterations],
            ['warm-up' => (string) $warmup],
            ['tree count' => (string) $this->treeCount],
            ['php' => \PHP_VERSION],
            ['jit' => $this->jitStatus()],
            ['hrtime() overhead' => \sprintf('%.1f ns per sample (not subtracted)', $timerOverheadNs)],
        );

        // A single fixed input for every measurement. Rotating inputs would add branch
        // mispredictions and cache effects that differ per engine and confound the comparison.
        $subject = FixtureCases::all()[0]['input'];

        $rows = [];
        $raw = [];

        foreach ($profiles as $profile) {
            foreach ($engines as $selected) {
                try {
                    $samples = $this->measure($selected->engine, $subject, $profile, $iterations, $warmup);
                } catch (RiskEngineUnavailable $e) {
                    $io->warning(\sprintf(
                        'Skipping %s (%s): %s',
                        $selected->name->value, $profile->value, $e->getMessage(),
                    ));

                    return Command::FAILURE;
                }

                $stats = self::summarise($samples);

                $rows[] = [
                    $profile->value,
                    $selected->name->value,
                    self::formatNs($stats['p50']),
                    self::formatNs($stats['p95']),
                    self::formatNs($stats['p99']),
                    self::formatNs($stats['mean']),
                    number_format(1_000_000_000 / $stats['mean'], 0).' /s',
                ];

                $raw[] = [
                    'profile' => $profile->value,
                    'engine' => $selected->name->value,
                    'treeCount' => $this->treeCount,
                    'iterations' => $iterations,
                    'jit' => $this->jitStatus(),
                    'timerOverheadNs' => round($timerOverheadNs, 1),
                    'samplesNs' => $samples,
                    ...array_map(static fn (float $v): float => round($v, 1), $stats),
                ];
            }
        }

        $io->table(
            ['profile', 'engine', 'p50', 'p95', 'p99', 'mean', 'inverse mean (not capacity)'],
            $rows,
        );

        if (null !== $path = $input->getOption('json')) {
            \assert(\is_string($path));
            file_put_contents($path, json_encode([
                'level' => 'micro',
                'php' => \PHP_VERSION,
                'jit' => $this->jitStatus(),
                'treeCount' => $this->treeCount,
                'iterations' => $iterations,
                'warmup' => $warmup,
                'timerOverheadNs' => round($timerOverheadNs, 1),
                'results' => $raw,
            ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR)."\n");

            $io->comment(\sprintf('Raw results written to %s', $path));
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<float> per-call durations in nanoseconds
     */
    private function measure(
        object $engine,
        RiskInput $subject,
        RiskProfile $profile,
        int $iterations,
        int $warmup,
    ): array {
        \assert($engine instanceof \App\Domain\RiskEngine);

        // Warm-up is not politeness. It builds the scoring model, opens the HTTP connection, and
        // lets the JIT trace the hot path. Without it the first samples measure one-off costs and
        // the p99 becomes a report on startup rather than on steady state.
        // The result is kept, not discarded. Partly because RiskEngine::assess() is marked
        // #[\NoDiscard], but mainly because a benchmark that throws its result away invites the
        // engine — or the JIT — to skip work the real caller would have needed.
        $reference = $engine->assess($subject, $profile);
        $expected = [$reference->score, $reference->status, $reference->reason];

        for ($i = 0; $i < $warmup; ++$i) {
            $assessment = $engine->assess($subject, $profile);
            if ([$assessment->score, $assessment->status, $assessment->reason] !== $expected) {
                throw RiskEngineUnavailable::malformedResponse('Microbenchmark warmup drift.');
            }
        }

        $samples = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $started = hrtime(true);
            $assessment = $engine->assess($subject, $profile);
            $samples[] = (float) (hrtime(true) - $started);
            if ([$assessment->score, $assessment->status, $assessment->reason] !== $expected) {
                throw RiskEngineUnavailable::malformedResponse('Microbenchmark decision drift.');
            }
        }

        // Every measured decision was checked outside its timed interval.

        return $samples;
    }

    /**
     * Measures what an empty timed interval costs, so the reader can judge the fastest numbers.
     */
    private function calibrateTimer(): float
    {
        $samples = [];

        for ($i = 0; $i < 10_000; ++$i) {
            $started = hrtime(true);
            $samples[] = (float) (hrtime(true) - $started);
        }

        sort($samples);

        return $samples[(int) (\count($samples) * 0.5)];
    }

    /**
     * @param list<float> $samples
     *
     * @return array{p50: float, p95: float, p99: float, mean: float, min: float, max: float}
     */
    private static function summarise(array $samples): array
    {
        sort($samples);
        $count = \count($samples);

        $percentile = static function (float $q) use ($samples, $count): float {
            // Nearest-rank on the sorted sample, clamped. No interpolation: with thousands of
            // samples the difference is noise, and an interpolated p99 invites more precision than
            // the underlying measurement supports.
            $index = (int) ceil($q * $count) - 1;

            return $samples[max(0, min($count - 1, $index))];
        };

        return [
            'p50' => $percentile(0.50),
            'p95' => $percentile(0.95),
            'p99' => $percentile(0.99),
            'mean' => array_sum($samples) / $count,
            'min' => $samples[0],
            'max' => $samples[$count - 1],
        ];
    }

    private static function formatNs(float $ns): string
    {
        return match (true) {
            $ns < 1_000 => \sprintf('%.0f ns', $ns),
            $ns < 1_000_000 => \sprintf('%.2f µs', $ns / 1_000),
            default => \sprintf('%.2f ms', $ns / 1_000_000),
        };
    }

    private function jitStatus(): string
    {
        if (!\function_exists('opcache_get_status')) {
            return 'unavailable';
        }

        $status = opcache_get_status(false);

        if (!\is_array($status) || !isset($status['jit']) || !\is_array($status['jit'])) {
            return 'off';
        }

        return true === ($status['jit']['on'] ?? false) ? 'tracing' : 'off';
    }
}
