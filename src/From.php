<?php
declare(strict_types=1);

namespace From;

final class From implements \IteratorAggregate
{
    private readonly \Traversable $iterator;

    public function __construct(mixed $input)
    {
        if (is_array($input)) {
            $it = new \ArrayIterator($input);
        } elseif ($input instanceof \IteratorAggregate) {
            $it = $input->getIterator();
        } elseif ($input instanceof \Traversable) {
            $it = $input;
        } else {
            throw new \InvalidArgumentException('Input must be array or Traversable.');
        }

        $this->iterator = $it;
    }

    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }

    public function toArray(): array
    {
        $r = [];
        foreach ($this->iterator as $key => $value) {
            $r[$key] = $value;
        }

        return $r;
    }

    public function evaluate(): self
    {
        return $this->iterator instanceof \ArrayIterator ? $this : new self($this->toArray());
    }

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

    public function mapKeys(callable $keyMapper): self
    {
        return new self((function () use ($keyMapper): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $keyMapper($value, $key) => $value;
            }
        })());
    }

    public function values(): self
    {
        return new self((function (): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $value;
            }
        })());
    }

    public function keys(): self
    {
        return new self((function (): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $key;
            }
        })());
    }

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

    public function append(mixed $element): self
    {
        return new self((function () use ($element): iterable {
            foreach ($this->iterator as $key => $value) {
                yield $key => $value;
            }
            yield $element;
        })());
    }

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

    public function unique(): self
    {
        return new self((function (): iterable {
            $seen = [];
            foreach ($this->iterator as $key => $value) {
                $hash = $value;
                if (!array_key_exists($hash, $seen)) {
                    $seen[$hash] = true;
                    yield $key => $value;
                }
            }
        })());
    }

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

    public function first(): mixed
    {
        foreach ($this->iterator as $value) {
            return $value;
        }

        return null;
    }

    public function any(callable $predicate): bool
    {
        foreach ($this->iterator as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public function all(callable $predicate): bool
    {
        foreach ($this->iterator as $key => $value) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

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
}
