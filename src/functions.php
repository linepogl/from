<?php

declare(strict_types=1);

namespace From;

use ArrayIterator;
use Exception;
use Iterator;
use IteratorAggregate;

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
        $it = new ArrayIterator($input);
    } elseif ($input instanceof IteratorAggregate) {
        $it = $input->getIterator();
    } elseif ($input instanceof Iterator) {
        $it = $input;
    } elseif (is_callable($input)) {
        $it = new LazyIterator($input);
    } else {
        throw new Exception('Cannot convert iterable to iterator');
    }

    /** @var Iterator<TValue> $it */
    return $it;
}
