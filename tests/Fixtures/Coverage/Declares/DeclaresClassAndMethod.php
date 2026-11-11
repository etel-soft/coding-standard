<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Declares;

use Etel\CodingStandardTests\Fixtures\Coverage\Src\Calculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;

/**
 * The whole class declared, then one of its methods: the class wins, for DeclaredCoverageTest.
 */
#[CoversClass(Calculator::class)]
#[CoversMethod(Calculator::class, 'add')]
final class DeclaresClassAndMethod {}
