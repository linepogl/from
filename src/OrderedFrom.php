<?php

declare(strict_types=1);

namespace From;

/**
 * @template TKey of int|string
 * @template TValue
 * @template-extends From<TKey, TValue>
 */
final class OrderedFrom extends From
{
    /**
     * @var array<(callable(array<TKey, TValue>&):void)> $sorters
     */
    private readonly array $sorters;

    /**
     * @template TComparable
     * @param From<TKey, TValue> $inner
     * @param callable(TValue, TKey): TComparable $hasher
     * @param bool $desc
     * @param array<(callable(array<TKey, TValue>&):void)> $sorters
     */
    public function __construct(private readonly From $inner, callable $hasher, bool $desc = false, array $sorters = [])
    {
        $this->sorters = array_merge([static function (array &$a) use ($hasher, $desc) {
            uksort(
                $a,
                $desc
                ? static fn ($k1, $k2) => $hasher($a[$k2], $k2) <=> $hasher($a[$k1], $k1)
                : static fn ($k1, $k2) => $hasher($a[$k1], $k1) <=> $hasher($a[$k2], $k2),
            );
        }], $sorters);

        parent::__construct((function (): iterable {
            $a = $this->inner->toArray();
            foreach ($this->sorters as $sorter) {
                $sorter($a);
            }
            foreach ($a as $key => $value) {
                yield $key => $value;
            }
        })());
    }

    /**
     * @template TComparable
     * @param callable(TValue, TKey): TComparable $hasher
     * @return OrderedFrom<TKey, TValue>
     */
    public function thenBy(callable $hasher, bool $desc = false): self
    {
        return new self($this->inner, $hasher, $desc, $this->sorters);
    }
}
