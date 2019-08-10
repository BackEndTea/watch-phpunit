<?php

declare(strict_types=1);

namespace Watcher\Test;

use Generator;
use PHPUnit\Framework\TestCase;
use function Watcher\endsWith;
use function Watcher\startsWith;

class FunctionTest extends TestCase
{
    /**
     * @dataProvider providesEndsWithCases
     */
    public function testEndsWith(string $value, string $suffix, bool $expected) : void
    {
        $this->assertSame($expected, endsWith($value, $suffix));
    }

    public function providesEndsWithCases(): Generator
    {
        yield ['foo', 'o', true];

        yield ['foo', 'b', false];

        yield ['/Users/me/Projects/watch-phpunit', 'Projects/watch-phpunit', true];
    }

    /**
     * @dataProvider providesStartsWithCases
     */
    public function testStartsWith(string $value, string $prefix, bool $expected): void
    {
        $this->assertSame($expected, startsWith($value, $prefix));
    }

    public function providesStartsWithCases(): Generator
    {
        yield ['foo', 'o', false];

        yield ['foo', 'f', true];

        yield ['/Users/me/Projects/watch-phpunit', '/Users/me/Projects', true];
    }
}
