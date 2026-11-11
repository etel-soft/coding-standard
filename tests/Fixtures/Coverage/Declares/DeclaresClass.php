<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Declares;

use Etel\CodingStandardTests\Fixtures\Coverage\Src\Calculator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * A test class declaring #[CoversClass], for DeclaredCoverageTest.
 */
#[CoversClass(Calculator::class)]
final class DeclaresClass {}
