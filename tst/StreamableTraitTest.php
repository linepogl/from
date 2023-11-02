<?php

declare(strict_types=1);

use From\Stream;
use From\Streamable;
use From\StreamableTrait;
use PHPUnit\Framework\TestCase;

class Dog
{
}

/**
 * @extends Stream<Dog>
 */
class DogStream extends Stream
{
}

class Cat
{
}

/**
 * @implements Streamable<Cat>
 */
class CatStream implements Streamable
{
    /** @use StreamableTrait<Cat> */
    use StreamableTrait;

    /** @var Cat[] */
    private readonly array $array;
    public function __construct()
    {
        $this->array = [new Cat(), new Cat(), new Cat()];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->array);
    }
}

/**
 * @internal
 */
final class StreamableTraitTest extends TestCase
{
    public function test_extends(): void
    {
        $dog1 = new Dog();
        $this->assertSame([$dog1], DogStream::from([$dog1])->toArray());
    }

    public function test_uses_trait(): void
    {
        $this->assertCount(3, new CatStream());
    }
}
