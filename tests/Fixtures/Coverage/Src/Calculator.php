<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Src;

use LogicException;

/**
 * Covered by its test, together with the trait it uses; its dead code left out.
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

    public function never(): never
    {
        throw new LogicException(message: 'Never called.'); // @codeCoverageIgnore the fixture's dead code
    }
}
