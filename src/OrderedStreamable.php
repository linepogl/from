<?php

declare(strict_types=1);

namespace From;

/**
 * @template TValue
 * @extends Streamable<TValue>
 */
interface OrderedStreamable extends Streamable
{
    /**
     * @template TComparable
     * @param callable(TValue, mixed): TComparable $hasher
     * @return self<TValue>
     */
    public function thenBy(callable $hasher, bool $desc = false): self;
}
