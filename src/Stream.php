<?php

declare(strict_types=1);

namespace From;

use ArrayIterator;
use Iterator;
use Override;
use Traversable;

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

    #[Override]
    public function getIterator(): Traversable
    {
        return $this->iterator;
    }

    #[Override]
    public function evaluate(): Streamable
    {
        return $this->iterator instanceof ArrayIterator ? $this : self::from($this->toArray());
    }

    /**
     * @template TInputValue
     * @param iterable<TInputValue> $input
     * @return self<TInputValue>
     */
    public static function from(iterable $input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @template TInputValue
     * @param callable(): iterable<TInputValue> $lazyIterableFunction
     * @return Streamable<TInputValue>
     */
    public static function lazy(callable $lazyIterableFunction): Streamable
    {
        return self::from(new LazyRewindableIterator($lazyIterableFunction));
    }

    /**
     * @return Streamable<TValue>
     */
    public static function empty(): Streamable
    {
        static $empty = new self([]);

        /** @var Streamable<TValue> $empty */
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
        return self::lazy(static function () use ($firstElement, $getNext): iterable {
            $i = 0;
            for ($x = $firstElement;; $x = $getNext($x, $i++)) {
                yield $x;
            }
        });
    }
}
