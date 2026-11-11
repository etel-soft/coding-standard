<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage;

use Etel\CodingStandardTests\Fixtures\Coverage\Src\Calculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Run by bin/etel-coverage in EtelCoverageTest, never by the package's own suite.
 *
 * @internal
 */
#[CoversClass(Calculator::class)]
final class CalculatorTest extends TestCase
{
    #[Test]
    #[TestDox('Adds and halves')]
    public function calculates(): void
    {
        $this->assertSame(0.3, new Calculator()->add(a: 0.1, b: 0.2));
        $this->assertSame(1.5, new Calculator()->half(value: 3));
    }
}
