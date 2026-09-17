<?php

declare(strict_types=1);

namespace App\Lab\Command;

use App\Domain\RiskEngineUnavailable;
use App\Domain\RiskProfile;
use App\Engine\EngineRegistry;
use App\Lab\FixtureCases;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The test that has to pass before any performance number is worth discussing.
 *
 * Runs every fixture case, under both profiles, through every engine this deployment can reach, and
 * requires all of them to produce identical verdicts — score, status *and* reason. Agreement on the
 * score alone would be too weak: two different rule sets can coincidentally total the same.
 *
 * Why this runs inside the API container rather than over HTTP: only this process has all three
 * engines reachable at once, and going through the public endpoint would compare API responses
 * instead of engine verdicts. A serialisation bug in the API layer would then look like an engine
 * disagreement.
 *
 * The output ends with one hash per engine over all verdicts in order. Three identical hashes is a
 * claim you can read from the back of a room; a table of 100 rows is not.
 */
#[AsCommand(
    name: 'lab:parity',
    description: 'Prove every available engine reaches identical decisions for every fixture',
)]
final class ParityCommand extends Command
{
    public function __construct(
        private readonly EngineRegistry $engines,
        private readonly int $treeCount,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $engines = $this->engines->available();
        $cases = FixtureCases::all();

        $io->title('Cross-engine parity');

        $unavailable = array_diff(
            array_map(static fn ($s) => $s->name->value, $this->engines->all()),
            array_map(static fn ($s) => $s->name->value, $engines),
        );

        if ([] !== $unavailable) {
            // Reported loudly rather than passing quietly. A green parity run that silently skipped
            // the native engine is worse than a red one, because it looks like proof.
            $io->warning(\sprintf(
                'Not reachable, so NOT verified: %s',
                implode(', ', $unavailable),
            ));
        }

        if (\count($engines) < 2) {
            $io->error('Parity needs at least two reachable engines. Is the stack up? (make start)');

            return Command::FAILURE;
        }

        $io->writeln(\sprintf(
            ' %d cases x %d profiles x %d engines = <options=bold>%d</> assessments (treeCount=%d)',
            \count($cases),
            \count(RiskProfile::cases()),
            \count($engines),
            \count($cases) * \count(RiskProfile::cases()) * \count($engines),
            $this->treeCount,
        ));
        $io->newLine();

        /** @var array<string, list<string>> $verdicts */
        $verdicts = [];
        /** @var list<array{string, string, string, string}> $mismatches */
        $mismatches = [];
        $compared = 0;

        foreach ($engines as $selected) {
            $verdicts[$selected->name->value] = [];
        }

        foreach ($cases as $case) {
            foreach (RiskProfile::cases() as $profile) {
                $reference = null;
                $referenceEngine = null;

                foreach ($engines as $selected) {
                    try {
                        $assessment = $selected->engine->assess($case['input'], $profile);
                    } catch (RiskEngineUnavailable $e) {
                        $io->error(\sprintf(
                            'Engine %s failed on case "%s" (%s): %s',
                            $selected->name->value, $case['id'], $profile->value, $e->getMessage(),
                        ));

                        return Command::FAILURE;
                    }

                    $signature = \sprintf(
                        '%d/%s/%s',
                        $assessment->score,
                        $assessment->status->value,
                        $assessment->reason->value,
                    );

                    $verdicts[$selected->name->value][] = $case['id'].':'.$profile->value.'='.$signature;

                    if (null === $reference) {
                        $reference = $signature;
                        $referenceEngine = $selected->name->value;
                        continue;
                    }

                    ++$compared;

                    if ($signature !== $reference) {
                        $mismatches[] = [
                            $case['id'].' ('.$profile->value.')',
                            \sprintf('%s: %s', (string) $referenceEngine, $reference),
                            \sprintf('%s: %s', $selected->name->value, $signature),
                            $case['description'],
                        ];
                    }
                }
            }
        }

        if ([] !== $mismatches) {
            $io->error(\sprintf('%d disagreement(s) found', \count($mismatches)));
            $io->table(['case', 'reference', 'differs', 'description'], $mismatches);

            return Command::FAILURE;
        }

        $io->table(
            ['engine', 'assessments', 'sha256 of all verdicts, in order'],
            array_map(
                static fn (string $engine, array $lines): array => [
                    $engine,
                    (string) \count($lines),
                    hash('sha256', implode("\n", $lines)),
                ],
                array_keys($verdicts),
                $verdicts,
            ),
        );

        $io->success(\sprintf(
            'Identical decisions across %s. %d pairwise comparisons, 0 disagreements.',
            implode(' / ', array_keys($verdicts)),
            $compared,
        ));

        return Command::SUCCESS;
    }
}
