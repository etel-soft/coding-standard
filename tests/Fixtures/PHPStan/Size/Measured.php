<?php

/* @noinspection ALL */

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\PHPStan\Size;

use ArrayObject;
use DateTimeImmutable;
use stdClass;

final class Measured
{
    public function __construct(
        private stdClass $first,
        private ?ArrayObject $second,
        private DateTimeImmutable $third,
        private int $count
    ) {}

    public function flat(): int
    {
        return 1;
    }

    /**
     * Complexity 12: foreach 1, if 1+1, && run 1, elseif 1, || run 1, else 1, ternary 1+2, arrow-fn ternary 1+1.
     *
     * @param list<int> $items
     */
    public function nested(array $items, bool $a, bool $b, bool $c): int
    {
        $total = 0;

        foreach ($items as $item) {
            if ($item > 0 && $a && $b) {
                ++$total;
            } elseif ($b || $c) {
                --$total;
            } else {
                $total += $a ? 1 : 2;
            }
        }

        $sign = static fn (int $x): int => $x > 0 ? 1 : 0;

        return $total + $sign($total);
    }

    /**
     * Complexity 6: while 1, foreach 1+1, break out of both loops 1, mixed operators (&& then ||) 2.
     */
    public function jumps(bool $a, bool $b, bool $c): void
    {
        while (true) {
            foreach ([1, 2] as $item) {
                break 2;
            }
        }

        $mixed = $a && $b || $c;
    }
}
