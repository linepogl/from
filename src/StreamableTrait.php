<?php

declare(strict_types=1);

namespace From;

use ArrayIterator;
use Traversable;

/**
 * @template TValue
 */
trait StreamableTrait
{
    /**
     * @return Traversable<TValue>
     */
    abstract public function getIterator(): Traversable;

    /**
     * @return array<TValue>
     */
    public function toArray(): array
    {
        $r = [];
        foreach ($this->getIterator() as $key => $value) {
            $r[$key] = $value;
        }

        return $r;
    }

    /**
     * @template TResult
     * @param callable(TValue, mixed): TResult $mapper
     * @param ?callable(TValue, mixed): mixed $keyMapper:
     * @return Streamable<TResult>
     */
    public function map(callable $mapper, ?callable $keyMapper = null): Streamable
    {
        return Stream::lazy(function () use ($mapper, $keyMapper): iterable {
            if ($keyMapper === null) {
                foreach ($this->getIterator() as $key => $value) {
                    yield $key => $mapper($value, $key);
                }
            } else {
                foreach ($this->getIterator() as $key => $value) {
                    yield $keyMapper($value, $key) => $mapper($value, $key);
                }
            }
        });
    }

    /**
     * @param callable(TValue, mixed): mixed $keyMapper:
     * @return Streamable<TValue>
     */
    public function mapKeys(callable $keyMapper): Streamable
    {
        return Stream::lazy(function () use ($keyMapper): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $keyMapper($value, $key) => $value;
            }
        });
    }

    /**
     * @return Streamable<TValue>
     */
    public function values(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $value;
            }
        });
    }

    /**
     * @return Streamable<int|string>
     */
    public function keys(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * @template TResult
     * @param callable(TValue, mixed): TResult $mapper
     * @return Streamable<TResult>
     */
    public function flatMap(callable $mapper): Streamable
    {
        return Stream::lazy(function () use ($mapper): iterable {
            foreach ($this->getIterator() as $key => $value) {
                $inner = $mapper($value, $key);
                foreach ($inner as $innerValue) {
                    yield $innerValue;
                }
            }
        });
    }

    /**
     * @param iterable<TValue> $other
     * @return Streamable<TValue>
     */
    public function merge(iterable $other): Streamable
    {
        return Stream::lazy(function () use ($other): iterable {
            $seen = [];
            foreach ($this->getIterator() as $key => $value) {
                if (is_int($key)) {
                    yield $value;
                } else {
                    $seen[$key] = true;
                    yield $key => $value;
                }
            }
            foreach ($other as $key => $value) {
                if (is_int($key)) {
                    yield $value;
                } elseif (!isset($seen[$key])) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @param TValue $element
     * @return Streamable<TValue>
     */
    public function append(mixed $element): Streamable
    {
        return Stream::lazy(function () use ($element): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $key => $value;
            }
            yield $element;
        });
    }

    /**
     * @param callable(TValue, mixed): bool $predicate
     * @return Streamable<TValue>
     */
    public function filter(callable $predicate): Streamable
    {
        return Stream::lazy(function () use ($predicate): iterable {
            foreach ($this->getIterator() as $key => $value) {
                if ($predicate($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @return Streamable<TValue>
     */
    public function compact(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                if ($value !== null) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @param callable(TValue, mixed): bool $predicate
     * @return Streamable<TValue>
     */
    public function reject(callable $predicate): Streamable
    {
        return Stream::lazy(function () use ($predicate): iterable {
            foreach ($this->getIterator() as $key => $value) {
                if (!$predicate($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @param ?callable(TValue, mixed): (int|string) $hasher
     * @return Streamable<TValue>
     */
    public function unique(?callable $hasher = null): Streamable
    {
        $hasher ??= static fn ($value) => is_int($value) ? $value : strval($value);

        return Stream::lazy(function () use ($hasher): iterable {
            $seen = [];
            foreach ($this->getIterator() as $key => $value) {
                $hash = $hasher($value, $key);
                if (!array_key_exists($hash, $seen)) {
                    $seen[$hash] = true;
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @param int $howMany
     * @return Streamable<TValue>
     */
    public function take(int $howMany): Streamable
    {
        return Stream::lazy(function () use ($howMany): iterable {
            $index = 0;
            foreach ($this->getIterator() as $key => $value) {
                if (++$index > $howMany) {
                    break;
                }
                yield $key => $value;
            }
        });
    }

    /**
     * @param int $howMany
     * @return Streamable<TValue>
     */
    public function skip(int $howMany): Streamable
    {
        return Stream::lazy(function () use ($howMany): iterable {
            $index = 0;
            foreach ($this->getIterator() as $key => $value) {
                if (++$index <= $howMany) {
                    continue;
                }
                yield $key => $value;
            }
        });
    }

    /**
     * @param ?callable(TValue, mixed): bool $predicate
     * @return ?TValue
     */
    public function first(?callable $predicate = null): mixed
    {
        if ($predicate !== null) {
            foreach ($this->getIterator() as $key => $value) {
                if ($predicate($value, $key)) {
                    return $value;
                }
            }
        } else {
            foreach ($this->getIterator() as $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param callable(TValue, mixed): bool $predicate
     * @return bool
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(TValue, mixed): bool $predicate
     * @return bool
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @template TResult
     * @param callable(TResult, TValue): TResult $operator
     * @param TResult $default
     * @return TResult
     */
    public function reduce(callable $operator, mixed $default = null): mixed
    {
        $r = $default;
        foreach ($this->getIterator() as $value) {
            $r = $operator($r, $value);
        }

        return $r;
    }

    public function implode(string $separator = ''): string
    {
        $r = '';
        foreach ($this->getIterator() as $value) {
            /** @phpstan-ignore-next-line because it fails when TValue is not string-able */
            $r .= $separator . $value;
        }

        return $r;
    }

    public function sum(): float
    {
        $r = 0.0;
        foreach ($this->getIterator() as $value) {
            $r += $value;
        }

        return $r;
    }

    public function count(): int
    {
        $it = $this->getIterator();
        if ($it instanceof ArrayIterator) {
            return $it->count();
        }

        $r = 0;
        foreach ($it as $ignored) {
            $r++;
        }

        return $r;
    }

    /**
     * @template TComparable
     * @param callable(TValue, mixed): TComparable $hasher
     * @return OrderedStream<TValue>
     */
    public function orderBy(callable $hasher, bool $desc = false): OrderedStream
    {
        return new OrderedStream($this, $hasher, $desc);
    }
}
