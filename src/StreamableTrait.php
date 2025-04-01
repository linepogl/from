<?php

declare(strict_types=1);

namespace From;

use OutOfBoundsException;
use Override;
use Traversable;

/**
 * @template TValue
 * @phpstan-require-implements Streamable<TValue>
 */
trait StreamableTrait
{
    #[Override]
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    #[Override]
    public function evaluate(): Streamable
    {
        return Stream::from($this->toArray());
    }

    #[Override]
    public function foreach(callable $callback): Streamable
    {
        foreach ($this->getIterator() as $key => $value) {
            $callback($value, $key);
        }

        return $this;
    }

    #[Override]
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

    #[Override]
    public function mapKeys(callable $keyMapper): Streamable
    {
        return Stream::lazy(function () use ($keyMapper): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $keyMapper($value, $key) => $value;
            }
        });
    }

    #[Override]
    public function values(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $value;
            }
        });
    }

    #[Override]
    public function keys(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $key;
            }
        });
    }

    #[Override]
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

    #[Override]
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

    #[Override]
    public function append(mixed $element): Streamable
    {
        return Stream::lazy(function () use ($element): iterable {
            foreach ($this->getIterator() as $key => $value) {
                yield $key => $value;
            }
            yield $element;
        });
    }

    #[Override]
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

    #[Override]
    public function compact(): Streamable
    {
        return Stream::lazy(function (): iterable {
            foreach ($this->getIterator() as $key => $value) {
                /**
                 * @phpstan-ignore-next-line notIdentical.alwaysTrue
                 * Here, phpstan is right. In many cases, we know at compile-time that TValue is not nullable. Yet,
                 * distinguishing these cases would result in a more complex usage of the library.
                 */
                if ($value !== null) {
                    yield $key => $value;
                }
            }
        });
    }

    #[Override]
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

    #[Override]
    public function unique(?callable $hasher = null): Streamable
    {
        /** @phpstan-ignore-next-line cast.string -- Here, phpstan is right. Yet, this is a best-effort default, assuming that TValue is Stringable. */
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

    #[Override]
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

    #[Override]
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

    #[Override]
    public function first(?callable $predicate = null): mixed
    {
        return $this->firstOrNull($predicate) ?? throw new OutOfBoundsException();
    }

    #[Override]
    public function firstOrNull(?callable $predicate = null): mixed
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

    #[Override]
    public function last(?callable $predicate = null): mixed
    {
        return $this->lastOrNull($predicate) ?? throw new OutOfBoundsException();
    }

    #[Override]
    public function lastOrNull(?callable $predicate = null): mixed
    {
        $last = null;

        if ($predicate !== null) {
            foreach ($this->getIterator() as $key => $value) {
                if ($predicate($value, $key)) {
                    $last = $value;
                }
            }
        } else {
            foreach ($this->getIterator() as $value) {
                $last = $value;
            }
        }

        return $last;
    }

    #[Override]
    public function any(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function all(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function reduce(callable $operator, mixed $default = null): mixed
    {
        $r = $default;
        foreach ($this->getIterator() as $value) {
            $r = $operator($r, $value);
        }

        return $r;
    }

    #[Override]
    public function implode(string $separator = ''): string
    {
        $r = '';
        foreach ($this->getIterator() as $value) {
            // @phpstan-ignore-next-line because it fails when TValue is not string-able
            $r .= $separator . $value;
        }

        return $r;
    }

    #[Override]
    public function sum(): float
    {
        $r = 0.0;
        /** @var numeric $value */
        foreach ($this->getIterator() as $value) {
            $r += $value;
        }

        return $r;
    }

    #[Override]
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    #[Override]
    public function orderBy(callable $hasher, bool $desc = false): OrderedStreamable
    {
        return new OrderedStream($this, $hasher, $desc);
    }

    #[Override]
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
