<?php

declare(strict_types=1);

namespace From;

use Generator;
use Iterator;
use RuntimeException;

/**
 * @template TValue
 * @implements Iterator<TValue>
 */
class LazyIterator implements Iterator
{
    /** @var callable(): iterable<TValue> */
    private readonly mixed $lazyIterable;

    /** @var ?Iterator<TValue> */
    private ?Iterator $iterator = null;

    /**
     * @param callable(): iterable<TValue> $lazyIterable
     */
    public function __construct(callable $lazyIterable)
    {
        $this->lazyIterable = $lazyIterable;
    }

    public function rewind(): void
    {
        if ($this->iterator === null || $this->iterator instanceof Generator) {
            $iterable = ($this->lazyIterable)();
            $this->iterator = iterable_to_iterator($iterable);
        }
        $this->iterator->rewind();
    }

    public function current(): mixed
    {
        if ($this->iterator === null) {
            throw new RuntimeException('The iterator must be rewound first');
        }
        return $this->iterator->current();
    }

    public function next(): void
    {
        if ($this->iterator === null) {
            throw new RuntimeException('The iterator must be rewound first');
        }

        $this->iterator->next();
    }

    public function key(): mixed
    {
        if ($this->iterator === null) {
            throw new RuntimeException('The iterator must be rewound first');
        }

        return $this->iterator->key();
    }

    public function valid(): bool
    {
        if ($this->iterator === null) {
            throw new RuntimeException('The iterator must be rewound first');
        }

        return $this->iterator->valid();
    }
}
