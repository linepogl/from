<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use function From\from;

/**
 * @internal
 */
final class FromTest extends TestCase
{
    public function test_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        from('');
    }

    public function test_get_iterator(): void
    {
        static::assertInstanceOf(\Traversable::class, from([0 => 'a', 1 => 'b'])->getIterator());
    }

    public function test_evaluate(): void
    {
        static::assertInstanceOf(\From\From::class, from([0 => 'a', 1 => 'b'])->evaluate());
    }

    public function test_map(): void
    {
        static::assertSame([], from([])->map(fn ($x) => $x + 1)->toArray());
        static::assertSame([2, 3, 4], from([1, 2, 3])->map(fn ($x) => $x + 1)->toArray());
        static::assertSame([1, 3, 5], from([1, 2, 3])->map(fn ($x, $k) => $x + $k)->toArray());
        static::assertSame(['a' => 2, 3, 4], from(['a' => 1, 2, 3])->map(fn ($x) => $x + 1)->toArray());
        static::assertSame(['a' => '1a', '20', '31'], from(['a' => 1, 2, 3])->map(fn ($x, $k) => $x . $k)->toArray());
    }

    public function test_flat_map(): void
    {
        static::assertSame([], from([])->flatMap(fn ($x, $y): array => [$x + $y])->toArray());
        static::assertSame([2, 3, 4], array_values(from([1, 2, 3])->flatMap(fn ($x, $y): array => [$x + 1])->toArray()));
    }

    public function test_map_keys(): void
    {
        static::assertSame(['a' => 'a', 'b' => 'b'], from([0 => 'a', 1 => 'b'])->mapKeys(fn ($x, $y) => $x)->toArray());
    }

    public function test_merge(): void
    {
        static::assertSame([], from([])->merge([])->toArray());
        static::assertSame([1, 2, 3, 4, 5, 6], from([1, 2, 3])->merge([4, 5, 6])->toArray());
        static::assertSame(['a' => 1, 2, 3, 'b' => 4, 5, 6], from(['a' => 1, 2, 3])->merge(['b' => 4, 5, 6])->toArray());
    }

    public function test_map_with_keys(): void
    {
        static::assertSame([], from([])->map(fn ($x) => $x + 1, fn ($x, $k) => $x . $k)->toArray());
        static::assertSame(['10' => 2, '21' => 3, '32' => 4], from([1, 2, 3])->map(fn ($x) => $x + 1, fn ($x, $k) => $x . $k)->toArray());
        static::assertSame(['10' => 1, '21' => 3, '32' => 5], from([1, 2, 3])->map(fn ($x, $k) => $x + $k, fn ($x, $k) => $x . $k)->toArray());
        static::assertSame(['1a' => 2, '20' => 3, '31' => 4], from(['a' => 1, 2, 3])->map(fn ($x) => $x + 1, fn ($x, $k) => $x . $k)->toArray());
        static::assertSame(['1a' => '1a', '20' => '20', '31' => '31'], from(['a' => 1, 2, 3])->map(fn ($x, $k) => $x . $k, fn ($x, $k) => $x . $k)->toArray());
    }

    public function test_take(): void
    {
        static::assertSame([], from([])->take(0)->toArray());
        static::assertSame([0 => 0, 1 => 1], from([0, 1, 2, 3, 4])->take(2)->toArray());
        static::assertSame([0, 1], array_values(from([0, 1, 2, 3, 4])->take(2)->toArray()));
    }

    public function test_skip(): void
    {
        static::assertSame([], from([])->skip(0)->toArray());
        static::assertSame([2 => 2, 3 => 3, 4 => 4], from([0, 1, 2, 3, 4])->skip(2)->toArray());
        static::assertSame([2, 3, 4], array_values(from([0, 1, 2, 3, 4])->skip(2)->toArray()));
    }

    public function test_append(): void
    {
        static::assertSame([1], from([])->append(1)->toArray());
        static::assertSame([0 => 1, 1 => 2, 2 => 3, [0 => 1, 1 => 2, 2 => 3]], from([1, 2, 3])->append([1, 2, 3])->toArray());
    }

    public function test_filter(): void
    {
        static::assertSame([], from([])->filter(fn ($x) => $x > 1)->toArray());
        static::assertSame([1 => 2, 2 => 3], from([1, 2, 3])->filter(fn ($x) => $x > 1)->toArray());
        static::assertSame([2 => 3], from([1, 2, 3])->filter(fn ($x, $k) => $k > 1)->toArray());
        static::assertSame([1], from([1, 2, 3])->filter(fn ($x, $k) => $k < 1)->toArray());
        static::assertSame([2, 3], from(['a' => 1, 2, 3])->filter(fn ($x) => $x > 1)->toArray());
        static::assertSame([2], from(['a' => 1, 2, 3])->filter(fn ($x, $k) => $k === 0)->toArray());
    }

    public function test_reject(): void
    {
        static::assertSame([], from([])->reject(fn ($x) => $x > 1)->toArray());
        static::assertSame([1], from([1, 2, 3])->reject(fn ($x) => $x > 1)->toArray());
        static::assertSame([1, 2], from([1, 2, 3])->reject(fn ($x, $k) => $k > 1)->toArray());
        static::assertSame([1 => 2, 2 => 3], from([1, 2, 3])->reject(fn ($x, $k) => $k < 1)->toArray());
        static::assertSame(['a' => 1], from(['a' => 1, 2, 3])->reject(fn ($x) => $x > 1)->toArray());
        static::assertSame(['a' => 1, 1 => 3], from(['a' => 1, 2, 3])->reject(fn ($x, $k) => $k === 0)->toArray());
    }

    public function test_any(): void
    {
        static::assertFalse(from([])->any(fn ($x) => $x > 1));
        static::assertTrue(from([1, 2, 3])->any(fn ($x) => $x > 1));
        static::assertFalse(from([1, 2, 3])->any(fn ($x) => $x < 0));
        static::assertTrue(from([1, 2, 3])->any(fn ($x, $k) => $k > 1));
        static::assertFalse(from([1, 2, 3])->any(fn ($x, $k) => $k < 0));
        static::assertTrue(from(['a' => 1, 2, 3])->any(fn ($x) => $x === 2));
        static::assertFalse(from(['a' => 1, 2, 3])->any(fn ($x) => $x === 4));
        static::assertTrue(from(['a' => 1, 2, 3])->any(fn ($x, $k) => $k === 'a'));
        static::assertFalse(from(['a' => 1, 2, 3])->any(fn ($x, $k) => $k === 'b'));
    }

    public function test_all(): void
    {
        static::assertTrue(from([])->all(fn ($x) => $x > 1));
        static::assertTrue(from([1, 2, 3])->all(fn ($x) => $x > 0));
        static::assertFalse(from([1, 2, 3])->all(fn ($x) => $x > 1));
        static::assertTrue(from([1, 2, 3])->all(fn ($x, $k) => $k < 3));
        static::assertFalse(from([1, 2, 3])->all(fn ($x, $k) => $k < 2));
        static::assertTrue(from(['a' => 1, 2, 3])->all(fn ($x) => $x > 0));
        static::assertFalse(from(['a' => 1, 2, 3])->all(fn ($x) => $x > 1));
        static::assertTrue(from(['a' => 1, 2, 3])->all(fn ($x, $k) => is_int($k) || $k === 'a'));
        static::assertFalse(from(['a' => 1, 2, 3])->all(fn ($x, $k) => $k === 'a'));
    }

    public function test_compact(): void
    {
        static::assertSame([], from([])->compact()->toArray());
        static::assertSame([0, 2 => 2, 3 => 3], from([0, null, 2, 3])->compact()->toArray());
        static::assertSame([2 => 3], from([null, null, 3])->compact()->toArray());
        static::assertSame(['a' => 1, 1 => 3], from(['a' => 1, null, 3])->compact()->toArray());
        static::assertSame([2, 3], from(['a' => null, 2, 3])->compact()->toArray());
    }

    public function test_keys(): void
    {
        static::assertSame([], from([])->keys()->toArray());
        static::assertSame([0, 1, 2], from([1, 2, 3])->keys()->toArray());
        static::assertSame(['a', 0, 1], from(['a' => 1, 2, 3])->keys()->toArray());
    }

    public function test_values(): void
    {
        static::assertSame([], from([])->values()->toArray());
        static::assertSame([1, 2, 3], from([1, 2, 3])->values()->toArray());
        static::assertSame([1, 2, 3], from(['a' => 1, 2, 3])->values()->toArray());
    }

    public function test_unique(): void
    {
        static::assertSame([], from([])->unique()->toArray());
        static::assertSame([0, 2, 1, 4 => 3], from([0, 2, 1, 2, 3])->unique()->toArray());
        static::assertSame([0, 'a' => 2, 1, 3 => 3], from([0, 'a' => 2, 1, 2, 'b' => 2, 3])->unique()->toArray());
    }

    public function test_first(): void
    {
        static::assertNull(from([])->first());
        static::assertSame(3, from([3, 2, 1, 0])->first());
    }

    public function test_reduce(): void
    {
        static::assertSame(0, from([3, 2, 1, 0])->reduce(fn ($x, $y) => $y));
        static::assertSame(1, from([3, 2, 1, 0])->reduce(fn ($x, $y) => $x, 1));
        static::assertSame('c', from(['a', 'b', 'c'])->reduce(fn ($x, $y) => $y));
    }

    public function test_implode(): void
    {
        static::assertSame(',a,b,c', from(['a', 'b', 'c'])->implode(','));
        static::assertSame(';a;b;c', from(['a', 'b', 'c'])->implode(';'));
    }

    public function test_sum(): void
    {
        static::assertSame(6.0, from([0, 1, 2, 3])->sum());
    }
}
