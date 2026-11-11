<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Declares;

use Etel\CodingStandardTests\Fixtures\Coverage\Src\Rounding;
use PHPUnit\Framework\Attributes\CoversTrait;

/**
 * A test class declaring #[CoversTrait], for DeclaredCoverageTest.
 */
#[CoversTrait(Rounding::class)]
final class DeclaresTrait {}
