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
 * @template T
 * @extends Stream<T>
 */
abstract class AnimalStream extends Stream
{
}

/** @extends AnimalStream<Dog> */
class DogStream extends AnimalStream
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

    #[Override]
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

        $a = new CatStream();
        $a->first();
    }

    /**
     * @return AnimalStream<Dog>
     */
    public function getDogStream(): AnimalStream
    {
        return new DogStream([new Dog()]);
    }
}
