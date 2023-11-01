<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function From\from;

/**
 * @internal
 */
final class FromTest extends TestCase
{
    public function test_get_iterator(): void
    {
        $this->assertInstanceOf(\Traversable::class, from([0 => 'a', 1 => 'b'])->getIterator());
    }

    public function test_evaluate(): void
    {
        $this->assertInstanceOf(\From\From::class, from([0 => 'a', 1 => 'b'])->evaluate());
    }

    public function test_map(): void
    {
        $this->assertSame([], from([])->map(fn ($x) => $x + 1)->toArray());
        $this->assertSame([2, 3, 4], from([1, 2, 3])->map(fn ($x) => $x + 1)->toArray());
        $this->assertSame([1, 3, 5], from([1, 2, 3])->map(fn ($x, $k) => $x + $k)->toArray());
        $this->assertSame(['a' => 2, 3, 4], from(['a' => 1, 2, 3])->map(fn ($x) => $x + 1)->toArray());
        $this->assertSame(['a' => '1a', '20', '31'], from(['a' => 1, 2, 3])->map(fn ($x, $k) => $x . $k)->toArray());
    }

    public function test_flat_map(): void
    {
        $this->assertSame([], from([])->flatMap(fn ($x, $y): array => [$x + $y])->toArray());
        $this->assertSame([2, 3, 4], array_values(from([1, 2, 3])->flatMap(fn ($x, $y): array => [$x + 1])->toArray()));
    }

    public function test_map_keys(): void
    {
        $this->assertSame(['a' => 'a', 'b' => 'b'], from([0 => 'a', 1 => 'b'])->mapKeys(fn ($x, $y) => $x)->toArray());
    }

    public function test_merge(): void
    {
        $this->assertSame([], from([])->merge([])->toArray());
        $this->assertSame([1, 2, 3, 4, 5, 6], from([1, 2, 3])->merge([4, 5, 6])->toArray());
        $this->assertSame(['a' => 1, 2, 3, 'b' => 4, 5, 6], from(['a' => 1, 2, 3])->merge(['b' => 4, 5, 6])->toArray());
    }

    public function test_map_with_keys(): void
    {
        $this->assertSame([], from([])->map(fn ($x) => $x + 1, fn ($x, $k) => $x . $k)->toArray());
        $this->assertSame(['10' => 2, '21' => 3, '32' => 4], from([1, 2, 3])->map(fn ($x) => $x + 1, fn ($x, $k) => $x . $k)->toArray());
        $this->assertSame(['10' => 1, '21' => 3, '32' => 5], from([1, 2, 3])->map(fn ($x, $k) => $x + $k, fn ($x, $k) => $x . $k)->toArray());
        $this->assertSame(['1a' => 2, '20' => 3, '31' => 4], from(['a' => 1, 2, 3])->map(fn ($x) => $x + 1, fn ($x, $k) => $x . $k)->toArray());
        $this->assertSame(['1a' => '1a', '20' => '20', '31' => '31'], from(['a' => 1, 2, 3])->map(fn ($x, $k) => $x . $k, fn ($x, $k) => $x . $k)->toArray());
    }

    public function test_take(): void
    {
        $this->assertSame([], from([])->take(0)->toArray());
        $this->assertSame([0 => 0, 1 => 1], from([0, 1, 2, 3, 4])->take(2)->toArray());
        $this->assertSame([0, 1], array_values(from([0, 1, 2, 3, 4])->take(2)->toArray()));
    }

    public function test_skip(): void
    {
        $this->assertSame([], from([])->skip(0)->toArray());
        $this->assertSame([2 => 2, 3 => 3, 4 => 4], from([0, 1, 2, 3, 4])->skip(2)->toArray());
        $this->assertSame([2, 3, 4], array_values(from([0, 1, 2, 3, 4])->skip(2)->toArray()));
    }

    public function test_append(): void
    {
        $this->assertSame([0 => 1], from([])->append(1)->toArray());
        $this->assertSame([0 => 1, 1 => 2, 2 => 3, 3 => 1], from([1, 2, 3])->append(1)->toArray());
    }

    public function test_filter(): void
    {
        $this->assertSame([], from([])->filter(fn ($x) => $x > 1)->toArray());
        $this->assertSame([1 => 2, 2 => 3], from([1, 2, 3])->filter(fn ($x) => $x > 1)->toArray());
        $this->assertSame([2 => 3], from([1, 2, 3])->filter(fn ($x, $k) => $k > 1)->toArray());
        $this->assertSame([1], from([1, 2, 3])->filter(fn ($x, $k) => $k < 1)->toArray());
        $this->assertSame([2, 3], from(['a' => 1, 2, 3])->filter(fn ($x) => $x > 1)->toArray());
        $this->assertSame([2], from(['a' => 1, 2, 3])->filter(fn ($x, $k) => $k === 0)->toArray());
    }

    public function test_reject(): void
    {
        $this->assertSame([], from([])->reject(fn ($x) => $x > 1)->toArray());
        $this->assertSame([1], from([1, 2, 3])->reject(fn ($x) => $x > 1)->toArray());
        $this->assertSame([1, 2], from([1, 2, 3])->reject(fn ($x, $k) => $k > 1)->toArray());
        $this->assertSame([1 => 2, 2 => 3], from([1, 2, 3])->reject(fn ($x, $k) => $k < 1)->toArray());
        $this->assertSame(['a' => 1], from(['a' => 1, 2, 3])->reject(fn ($x) => $x > 1)->toArray());
        $this->assertSame(['a' => 1, 1 => 3], from(['a' => 1, 2, 3])->reject(fn ($x, $k) => $k === 0)->toArray());
    }

    public function test_any(): void
    {
        $this->assertFalse(from([])->any(fn ($x) => $x > 1));
        $this->assertTrue(from([1, 2, 3])->any(fn ($x) => $x > 1));
        $this->assertFalse(from([1, 2, 3])->any(fn ($x) => $x < 0));
        $this->assertTrue(from([1, 2, 3])->any(fn ($x, $k) => $k > 1));
        $this->assertFalse(from([1, 2, 3])->any(fn ($x, $k) => $k < 0));
        $this->assertTrue(from(['a' => 1, 2, 3])->any(fn ($x) => $x === 2));
        $this->assertFalse(from(['a' => 1, 2, 3])->any(fn ($x) => $x === 4));
        $this->assertTrue(from(['a' => 1, 2, 3])->any(fn ($x, $k) => $k === 'a'));
        $this->assertFalse(from(['a' => 1, 2, 3])->any(fn ($x, $k) => $k === 'b'));
    }

    public function test_all(): void
    {
        $this->assertTrue(from([])->all(fn ($x) => $x > 1));
        $this->assertTrue(from([1, 2, 3])->all(fn ($x) => $x > 0));
        $this->assertFalse(from([1, 2, 3])->all(fn ($x) => $x > 1));
        $this->assertTrue(from([1, 2, 3])->all(fn ($x, $k) => $k < 3));
        $this->assertFalse(from([1, 2, 3])->all(fn ($x, $k) => $k < 2));
        $this->assertTrue(from(['a' => 1, 2, 3])->all(fn ($x) => $x > 0));
        $this->assertFalse(from(['a' => 1, 2, 3])->all(fn ($x) => $x > 1));
        $this->assertTrue(from(['a' => 1, 2, 3])->all(fn ($x, $k) => is_int($k) || $k === 'a'));
        $this->assertFalse(from(['a' => 1, 2, 3])->all(fn ($x, $k) => $k === 'a'));
    }

    public function test_compact(): void
    {
        $this->assertSame([], from([])->compact()->toArray());
        $this->assertSame([0, 2 => 2, 3 => 3], from([0, null, 2, 3])->compact()->toArray());
        $this->assertSame([2 => 3], from([null, null, 3])->compact()->toArray());
        $this->assertSame(['a' => 1, 1 => 3], from(['a' => 1, null, 3])->compact()->toArray());
        $this->assertSame([2, 3], from(['a' => null, 2, 3])->compact()->toArray());
    }

    public function test_keys(): void
    {
        $this->assertSame([], from([])->keys()->toArray());
        $this->assertSame([0, 1, 2], from([1, 2, 3])->keys()->toArray());
        $this->assertSame(['a', 0, 1], from(['a' => 1, 2, 3])->keys()->toArray());
    }

    public function test_values(): void
    {
        $this->assertSame([], from([])->values()->toArray());
        $this->assertSame([1, 2, 3], from([1, 2, 3])->values()->toArray());
        $this->assertSame([1, 2, 3], from(['a' => 1, 2, 3])->values()->toArray());
    }

    public function test_unique(): void
    {
        $this->assertSame([], from([])->unique()->toArray());
        $this->assertSame([0, 2, 1, 4 => 3], from([0, 2, 1, 2, 3])->unique()->toArray());
        $this->assertSame([0, 'a' => 2, 1, 3 => 3], from([0, 'a' => 2, 1, 2, 'b' => 2, 3])->unique()->toArray());
    }

    public function test_first(): void
    {
        $this->assertNull(from([])->first());
        $this->assertSame(3, from([3, 2, 1, 0])->first());
    }

    public function test_reduce(): void
    {
        $this->assertSame(0, from([3, 2, 1, 0])->reduce(fn ($x, $y) => $y));
        $this->assertSame(1, from([3, 2, 1, 0])->reduce(fn ($x, $y) => $x, 1));
        $this->assertSame('c', from(['a', 'b', 'c'])->reduce(fn ($x, $y) => $y));
    }

    public function test_implode(): void
    {
        $this->assertSame(',a,b,c', from(['a', 'b', 'c'])->implode(','));
        $this->assertSame(';a;b;c', from(['a', 'b', 'c'])->implode(';'));
    }

    public function test_sum(): void
    {
        $this->assertSame(6.0, from([0, 1, 2, 3])->sum());
    }

    public function test_order_by(): void
    {
        $a = [
            'a' => ['a' => 9, 'b' => 10],
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
        ];
        $this->assertSame([
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
            'a' => ['a' => 9, 'b' => 10],
        ], from($a)->orderBy(fn ($x) => $x['a'])->toArray());
        $this->assertSame([
            'a' => ['a' => 9, 'b' => 10],
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
        ], from($a)->orderBy(fn ($x) => $x['a'], desc: true)->toArray());
        $this->assertSame([
            'c' => ['a' => 2, 'b' => 1],
            'b' => ['a' => 2, 'b' => 30],
            'a' => ['a' => 9, 'b' => 10],
        ], from($a)->orderBy(fn ($x) => $x['a'])->thenBy(fn ($x) => $x['b'])->toArray());
        $this->assertSame([
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
            'a' => ['a' => 9, 'b' => 10],
        ], from($a)->orderBy(fn ($x) => $x['a'])->thenBy(fn ($x) => $x['b'], desc: true)->toArray());
        $this->assertSame([
            'a' => ['a' => 9, 'b' => 10],
            'c' => ['a' => 2, 'b' => 1],
            'b' => ['a' => 2, 'b' => 30],
        ], from($a)->orderBy(fn ($x) => $x['a'], desc: true)->thenBy(fn ($x) => $x['b'])->toArray());
        $this->assertSame([
            'a' => ['a' => 9, 'b' => 10],
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
        ], from($a)->orderBy(fn ($x) => $x['a'], desc: true)->thenBy(fn ($x) => $x['b'], desc: true)->toArray());

        $this->assertSame([
            'a' => ['a' => 9, 'b' => 10],
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
        ], from($a)->orderBy(fn ($x, $k) => $k)->toArray());
        $this->assertSame([
            'c' => ['a' => 2, 'b' => 1],
            'b' => ['a' => 2, 'b' => 30],
            'a' => ['a' => 9, 'b' => 10],
        ], from($a)->orderBy(fn ($x, $k) => $k, desc: true)->toArray());
        $this->assertSame([
            'b' => ['a' => 2, 'b' => 30],
            'c' => ['a' => 2, 'b' => 1],
            'a' => ['a' => 9, 'b' => 10],
        ], from($a)->orderBy(fn ($x) => $x['a'])->thenBy(fn ($x, $k) => $k)->toArray());
        $this->assertSame([
            'c' => ['a' => 2, 'b' => 1],
            'b' => ['a' => 2, 'b' => 30],
            'a' => ['a' => 9, 'b' => 10],
        ], from($a)->orderBy(fn ($x) => $x['a'])->thenBy(fn ($x, $k) => $k, desc: true)->toArray());
    }
}
