<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Src;

/**
 * Covered by its test, together with the trait it uses.
 */
final class Calculator
{
    use Rounding;

    public function add(float $a, float $b): float
    {
        return $this->round(value: $a + $b);
    }

    public function half(float $value): float
    {
        return new Helper()->half(value: $value);
    }
}
