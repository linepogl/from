<?php

declare(strict_types=1);

namespace From;

use ArrayIterator;
use Generator;
use Iterator;

/**
 * @template TValue
 * @implements Streamable<TValue>
 */
class Stream implements Streamable
{
    /** @use StreamableTrait<TValue> */
    use StreamableTrait;

    /** @var Iterator<TValue> */
    protected readonly Iterator $iterator;

    /**
     * @param iterable<TValue> $input
     */
    public function __construct(iterable $input)
    {
        $this->iterator = iterable_to_iterator($input);
    }

    /**
     * @return Iterator<TValue>
     */
    public function getIterator(): Iterator
    {
        return $this->iterator;
    }

    /**
     * @return Streamable<TValue>
     */
    public function evaluate(): Streamable
    {
        return $this->iterator instanceof ArrayIterator ? $this : Stream::from($this->toArray());
    }

    /**
     * @template TInputValue
     * @param iterable<TInputValue> $input
     * @return self<TInputValue>
     */
    public static function from(iterable $input): self
    {
        return $input instanceof Stream ? $input : new self($input);
    }

    /**
     * @template TInputValue
     * @param callable(): Generator<TInputValue> $generatorFunction
     * @return Streamable<TInputValue>
     */
    public static function wrap(callable $generatorFunction): Streamable
    {
        return self::from(new LazyIterator($generatorFunction));
    }

    /**
     * @return Streamable<TValue>
     */
    public static function empty(): Streamable
    {
        static $empty = new self([]);
        return $empty;
    }

    /**
     * @template TInputValue
     * @param TInputValue $firstElement
     * @param callable(TInputValue, int): TInputValue $getNext
     * @return Streamable<TInputValue>
     */
    public static function unfold(mixed $firstElement, callable $getNext): Streamable
    {
        return self::wrap(static function () use ($firstElement, $getNext): iterable {
            $i = 0;
            for ($x = $firstElement; ; $x = $getNext($x, $i++)) {
                yield $x;
            }
        });
    }
}
