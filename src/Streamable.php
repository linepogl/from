<?php

declare(strict_types=1);

namespace From;

use IteratorAggregate;

/**
 * @template TValue
 * @extends IteratorAggregate<array-key, TValue>
 */
interface Streamable extends IteratorAggregate
{
    /**
     * @return array<TValue>
     */
    public function toArray(): array;

    /**
     * @return self<TValue>
     */
    public function evaluate(): self;

    /**
     * @param callable(TValue, mixed): void $callback
     * @return self<TValue>
     */
    public function foreach(callable $callback): self;

    /**
     * @template TResult
     * @template TResultKey
     * @param callable(TValue, array-key): TResult $mapper
     * @param ?callable(TValue, array-key): TResultKey $keyMapper:
     * @return self<TResult>
     */
    public function map(callable $mapper, ?callable $keyMapper = null): self;

    /**
     * @template TResultKey
     * @param callable(TValue, array-key): TResultKey $keyMapper:
     * @return self<TValue>
     */
    public function mapKeys(callable $keyMapper): self;

    /**
     * @return self<TValue>
     */
    public function values(): self;

    /**
     * @return self<array-key>
     */
    public function keys(): self;

    /**
     * @template TResult
     * @param callable(TValue, array-key): iterable<TResult> $mapper
     * @return self<TResult>
     */
    public function flatMap(callable $mapper): self;

    /**
     * @param iterable<TValue> $other
     * @return self<TValue>
     */
    public function merge(iterable $other): self;

    /**
     * @param TValue $element
     * @return self<TValue>
     */
    public function append(mixed $element): self;

    /**
     * @param callable(TValue, array-key): bool $predicate
     * @return self<TValue>
     */
    public function filter(callable $predicate): self;

    /**
     * @return self<TValue>
     */
    public function compact(): self;

    /**
     * @param callable(TValue, array-key): bool $predicate
     * @return self<TValue>
     */
    public function reject(callable $predicate): self;

    /**
     * @param ?callable(TValue, array-key): array-key $hasher
     * @return self<TValue>
     */
    public function unique(?callable $hasher = null): self;

    /**
     * @param int $howMany
     * @return self<TValue>
     */
    public function take(int $howMany): self;

    /**
     * @param int $howMany
     * @return self<TValue>
     */
    public function skip(int $howMany): self;

    /**
     * @param ?callable(TValue, array-key): bool $predicate
     * @return TValue
     */
    public function first(?callable $predicate = null): mixed;

    /**
     * @param ?callable(TValue, array-key): bool $predicate
     * @return ?TValue
     */
    public function firstOrNull(?callable $predicate = null): mixed;

    /**
     * @param ?callable(TValue, array-key): bool $predicate
     * @return TValue
     */
    public function last(?callable $predicate = null): mixed;

    /**
     * @param ?callable(TValue, array-key): bool $predicate
     * @return ?TValue
     */
    public function lastOrNull(?callable $predicate = null): mixed;

    /**
     * @param callable(TValue, array-key): bool $predicate
     * @return bool
     */
    public function any(callable $predicate): bool;

    /**
     * @param callable(TValue, array-key): bool $predicate
     * @return bool
     */
    public function all(callable $predicate): bool;

    /**
     * @template TResult
     * @param callable(TResult, TValue): TResult $operator
     * @param TResult $default
     * @return TResult
     */
    public function reduce(callable $operator, mixed $default = null): mixed;

    public function implode(string $separator = ''): string;

    public function sum(): float;

    public function count(): int;

    /**
     * @template TComparable
     * @param callable(TValue, array-key): TComparable $hasher
     * @return OrderedStreamable<TValue>
     */
    public function orderBy(callable $hasher, bool $desc = false): OrderedStreamable;

    /**
     * @param callable(TValue, array-key): array-key $hasher
     * @return Streamable<Streamable<TValue>>
     */
    public function groupBy(callable $hasher): self;
}
