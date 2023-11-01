<?php

declare(strict_types=1);

namespace From;

/**
 * @template TKey of int|string
 * @template TValue
 * @param iterable<TKey, TValue> $input
 * @return From<TKey, TValue>
 */
function from(iterable $input): From
{
    return $input instanceof From ? $input : new From($input);
}
