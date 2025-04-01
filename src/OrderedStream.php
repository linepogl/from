<?php

declare(strict_types=1);

namespace From;

use Override;

/**
 * @template TValue
 * @extends Stream<TValue>
 * @implements OrderedStreamable<TValue>
 */
final class OrderedStream extends Stream implements OrderedStreamable
{
    /**
     * @var array<(callable(array<TValue>&):void)> $sorters
     */
    private readonly array $sorters;

    /**
     * @template TComparable
     * @param Streamable<TValue> $inner
     * @param callable(TValue, array-key): TComparable $hasher
     * @param bool $desc
     * @param array<(callable(array<TValue>&):void)> $sorters
     */
    public function __construct(private readonly Streamable $inner, callable $hasher, bool $desc = false, array $sorters = [])
    {
        $this->sorters = array_merge([static function (array &$a) use ($hasher, $desc) {
            uksort(
                $a,
                $desc
                ? static fn (int|string $k1, int|string $k2) => $hasher($a[$k2], $k2) <=> $hasher($a[$k1], $k1)
                : static fn (int|string $k1, int|string $k2) => $hasher($a[$k1], $k1) <=> $hasher($a[$k2], $k2),
            );
        }], $sorters);

        parent::__construct(new LazyRewindableIterator(function () {
            $a = $this->inner->toArray();
            foreach ($this->sorters as $sorter) {
                $sorter($a);
                /** @var array<TValue> $a -- stating that the sorter won't change the type of the passed-by-ref argument */
            }
            foreach ($a as $key => $value) {
                yield $key => $value;
            }
        }));
    }

    /**
     * @template TComparable
     * @param callable(TValue, mixed): TComparable $hasher
     * @return OrderedStreamable<TValue>
     */
    #[Override]
    public function thenBy(callable $hasher, bool $desc = false): OrderedStreamable
    {
        return new self($this->inner, $hasher, $desc, $this->sorters);
    }
}
