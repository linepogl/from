<?php
declare(strict_types=1);

namespace From;

final class OrderedFrom extends From
{
    private readonly array $sorters;

    public function __construct(private readonly From $inner, callable $mapper, bool $desc = false, array $sorters = [])
    {
        $this->sorters = array_merge([function (array &$a) use ($mapper, $desc) {
            uksort($a, $desc
                ? fn($k1, $k2) => $mapper($a[$k2], $k2) <=> $mapper($a[$k1], $k1)
                : fn($k1, $k2) => $mapper($a[$k1], $k1) <=> $mapper($a[$k2], $k2)
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

    public function thenBy(callable $mapper, bool $desc = false): self
    {
        return new self($this->inner, $mapper, $desc, $this->sorters);
    }
}
