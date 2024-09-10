<?php

declare(strict_types=1);

namespace From;

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

    /** {@inheritdoc} */
    public function toArray(): array
    {
        $r = [];
        foreach ($this->getIterator() as $key => $value) {
            $r[$key] = $value;
        }

        return $r;
    }

    /** {@inheritdoc} */
    public function evaluate(): Streamable
    {
        return Stream::from($this->toArray());
    }


    /** {@inheritdoc} */
    public function foreach(callable $callback): Streamable
    {
        foreach ($this->getIterator() as $key => $value) {
            $callback($value, $key);
        }

        return $this;
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function mapKeys(callable $keyMapper): Streamable
    {
        return Stream::lazy(function () use ($keyMapper): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $keyMapper($value, $key) => $value;
            }
        });
    }

    /** {@inheritdoc} */
    public function values(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $value;
            }
        });
    }

    /** {@inheritdoc} */
    public function keys(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $key;
            }
        });
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function append(mixed $element): Streamable
    {
        return Stream::lazy(function () use ($element): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $key => $value;
            }
            yield $element;
        });
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function unique(?callable $hasher = null): Streamable
    {
        $hasher ??= static fn ($value) => is_int($value) ? $value : (string) $value;

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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function any(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /** {@inheritdoc} */
    public function all(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /** {@inheritdoc} */
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
            // @phpstan-ignore-next-line because it fails when TValue is not string-able
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
        return iterator_count($this->getIterator());
    }

    /** {@inheritdoc} */
    public function orderBy(callable $hasher, bool $desc = false): OrderedStreamable
    {
        return new OrderedStream($this, $hasher, $desc);
    }

    /** {@inheritdoc} */
    public function groupBy(callable $hasher): Streamable
    {
        return Stream::lazy(function () use ($hasher): iterable {
            $a = [];
            foreach ($this->getIterator() as $key => $value) {
                $groupKey = $hasher($value, $key);
                $a[$groupKey] ??= [];
                $a[$groupKey][] = $value;
            }
            foreach ($a as $groupKey => $groupValues) {
                yield $groupKey => Stream::from($groupValues);
            }
        });
    }
}
