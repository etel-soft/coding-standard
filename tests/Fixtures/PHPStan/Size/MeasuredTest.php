<?php

/* @noinspection ALL */

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\PHPStan\Size;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * A test class: only its length is checked, against the test limit.
 *
 * @internal
 */
final class MeasuredTest extends TestCase
{
    public function __construct(stdClass $first, stdClass $second, DateTimeImmutable $third)
    {
        parent::__construct(name: 'measured');
    }

    public function testBranches(bool $a, bool $b): void
    {
        if ($a) {
            if ($b) {
                foreach ([1, 2] as $item) {
                    $this->assertSame($item, $item);
                }
            }
        }
    }
}
