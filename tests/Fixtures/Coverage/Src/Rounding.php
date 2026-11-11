<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Src;

use function round;

/**
 * Counted with the class that uses it.
 */
trait Rounding
{
    private function round(float $value): float
    {
        return round(num: $value, precision: 2);
    }
}
