<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Declares;

use Etel\CodingStandardTests\Fixtures\Coverage\Src\Calculator;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Small;

/**
 * Methods and a function, plus an attribute that is not about coverage, for DeclaredCoverageTest.
 */
#[CoversMethod(Calculator::class, 'add')]
#[CoversMethod(Calculator::class, 'half')]
#[CoversFunction('Etel\CodingStandardTests\Fixtures\Coverage\Src\double')]
#[Small]
final class DeclaresRanges {}
