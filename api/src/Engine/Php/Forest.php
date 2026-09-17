<?php

declare(strict_types=1);

namespace App\Engine\Php;

/**
 * A generated decision-tree ensemble, stored flat.
 *
 * Trees are complete binary trees of fixed depth, so the children of node i are 2i+1 and 2i+2
 * and no node objects are needed. Three packed integer lists instead of ~16 000 small objects
 * is not micro-optimisation here: an object graph would make the PHP engine's cost dominated
 * by pointer chasing and would misrepresent what PHP is capable of.
 */
final readonly class Forest
{
    /**
     * @param list<int> $featureIdx length treeCount * 63
     * @param list<int> $threshold  length treeCount * 63
     * @param list<int> $leaves     length treeCount * 64
     */
    public function __construct(
        public array $featureIdx,
        public array $threshold,
        public array $leaves,
        public int $treeCount,
    ) {
    }
}
