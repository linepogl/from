<?php

declare(strict_types=1);

namespace From;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey of int|string
 * @template TValue
 * @template-implements IteratorAggregate<TKey, TValue>
 */
class From implements IteratorAggregate
{
    /** @var Traversable<TKey, TValue> */
    protected readonly Traversable $iterator;

    /**
     * @param iterable<TKey, TValue> $input
     */
    public function __construct(iterable $input)
    {
        if (is_array($input)) {
            $it = new ArrayIterator($input);
        } elseif ($input instanceof IteratorAggregate) {
            $it = $input->getIterator();
        } elseif ($input instanceof Traversable) {
            $it = $input;
        } else {
            throw new InvalidArgumentException('Input must be array or Traversable.');
        }

        $this->iterator = $it;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return $this->iterator;
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        $r = [];
        foreach ($this->iterator as $key => $value) {
            $r[$key] = $value;
        }

        return $r;
    }

    /**
     * @return self<TKey, TValue>
     */
    public function evaluate(): self
    {
        return $this->iterator instanceof ArrayIterator ? $this : new self($this->toArray());
    }

    /**
     * @template TResult
     * @param callable(TValue, TKey): TResult $mapper
     * @param ?callable(TValue, TKey): (string|int) $keyMapper:
     * @return self<TKey, TResult>
     */
    public function map(callable $mapper, ?callable $keyMapper = null): self
    {
        return new self((function () use ($mapper, $keyMapper): iterable {
            if ($keyMapper === null) {
                foreach ($this->iterator as $key => $value) {
                    yield $key => $mapper($value, $key);
                }
            } else {
                foreach ($this->iterator as $key => $value) {
                    yield $keyMapper($value, $key) => $mapper($value, $key);
                }
            }
        })());
    }

    /**
     * @param callable(TValue, TKey): TKey $keyMapper:
     * @return self<TKey, TValue>
     */
    public function mapKeys(callable $keyMapper): self
    {
        return new self((function () use ($keyMapper): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $keyMapper($value, $key) => $value;
            }
        })());
    }

    /**
     * @return self<int, TValue>
     */
    public function values(): self
    {
        return new self((function (): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $value;
            }
        })());
    }

    /**
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return new self((function (): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $key;
            }
        })());
    }

    /**
     * @template TResult
     * @param callable(TValue, TKey): TResult $mapper
     * @return self<int, TResult>
     */
    public function flatMap(callable $mapper): self
    {
        return new self((function () use ($mapper): iterable {
            foreach ($this->iterator as $key => $value) {
                $inner = $mapper($value, $key);
                foreach ($inner as $innerValue) {
                    yield $innerValue;
                }
            }
        })());
    }

    /**
     * @param iterable<TKey, TValue> $other
     * @return self<TKey, TValue>
     */
    public function merge(iterable $other): self
    {
        return new self((function () use ($other): iterable {
            $seen = [];
            foreach ($this->iterator as $key => $value) {
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
        })());
    }

    /**
     * @param TValue $element
     * @return self<TKey, TValue>
     */
    public function append(mixed $element): self
    {
        return new self((function () use ($element): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $key => $value;
            }
            yield $element;
        })());
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     * @return self<TKey, TValue>
     */
    public function filter(callable $predicate): self
    {
        return new self((function () use ($predicate): iterable {
            foreach ($this->iterator as $key => $value) {
                if ($predicate($value, $key)) {
                    yield $key => $value;
                }
            }
        })());
    }

    /**
     * @return self<TKey, TValue>
     */
    public function compact(): self
    {
        return new self((function (): iterable {
            foreach ($this->iterator as $key => $value) {
                if ($value !== null) {
                    yield $key => $value;
                }
            }
        })());
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     * @return self<TKey, TValue>
     */
    public function reject(callable $predicate): self
    {
        return new self((function () use ($predicate): iterable {
            foreach ($this->iterator as $key => $value) {
                if (!$predicate($value, $key)) {
                    yield $key => $value;
                }
            }
        })());
    }

    /**
     * @param ?callable(TValue, TKey): (string|int) $hasher
     * @return self<TKey, TValue>
     */
    public function unique(?callable $hasher = null): self
    {
        $hasher ??= static fn ($value) => is_int($value) ? $value : strval($value);

        return new self((function () use ($hasher): iterable {
            $seen = [];
            foreach ($this->iterator as $key => $value) {
                $hash = $hasher($value, $key);
                if (!array_key_exists($hash, $seen)) {
                    $seen[$hash] = true;
                    yield $key => $value;
                }
            }
        })());
    }

    /**
     * @param int $howMany
     * @return self<TKey, TValue>
     */
    public function take(int $howMany): self
    {
        return new self((function () use ($howMany): iterable {
            $index = 0;
            foreach ($this->iterator as $key => $value) {
                if (++$index > $howMany) {
                    break;
                }
                yield $key => $value;
            }
        })());
    }

    /**
     * @param int $howMany
     * @return self<TKey, TValue>
     */
    public function skip(int $howMany): self
    {
        return new self((function () use ($howMany): iterable {
            $index = 0;
            foreach ($this->iterator as $key => $value) {
                if (++$index <= $howMany) {
                    continue;
                }
                yield $key => $value;
            }
        })());
    }

    /**
     * @return ?TValue
     */
    public function first(): mixed
    {
        foreach ($this->iterator as $value) {
            return $value;
        }

        return null;
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     * @return bool
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->iterator as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     * @return bool
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->iterator as $key => $value) {
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
        foreach ($this->iterator as $value) {
            $r = $operator($r, $value);
        }

        return $r;
    }

    public function implode(string $separator = ''): string
    {
        $r = '';
        foreach ($this->iterator as $value) {
            $r .= $separator . $value;
        }

        return $r;
    }

    public function sum(): float
    {
        $r = 0.0;
        foreach ($this->iterator as $value) {
            $r += $value;
        }

        return $r;
    }

    /**
     * @template TComparable
     * @param callable(TValue, TKey): TComparable $hasher
     * @return OrderedFrom<TKey, TValue>
     */
    public function orderBy(callable $hasher, bool $desc = false): OrderedFrom
    {
        return new OrderedFrom($this, $hasher, $desc);
    }
}
