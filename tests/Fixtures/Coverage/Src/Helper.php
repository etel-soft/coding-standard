<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Src;

/**
 * Run by the calculator's test but declared by none: stays uncovered.
 */
final class Helper
{
    public function half(float $value): float
    {
        return $value / 2;
    }
}
