<?php

declare(strict_types=1);

namespace From;

use ArrayIterator;
use Exception;
use Iterator;
use IteratorAggregate;
use IteratorIterator;

/**
 * @template TValue
 * @param iterable<TValue> $input
 * @return Stream<TValue>
 */
function from(iterable $input): Stream
{
    return Stream::from($input);
}

/**
 * @template TValue
 * @param iterable<TValue> $input
 * @return Iterator<TValue>
 * @throws Exception
 */
function iterable_to_iterator(iterable $input): Iterator
{
    if (is_array($input)) {
        return new ArrayIterator($input);
    }

    if ($input instanceof Iterator) {
        return $input;
    }

    if ($input instanceof IteratorAggregate) {
        /** @phpstan-ignore-next-line return.type -- best effort */
        return iterable_to_iterator($input->getIterator());
    }

    if (is_callable($input)) {
        /** @phpstan-ignore-next-line return.type -- best effort */
        return new LazyRewindableIterator($input);
    }

    return new IteratorIterator($input);
}
