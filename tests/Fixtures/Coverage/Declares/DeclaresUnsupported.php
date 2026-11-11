<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\Coverage\Declares;

use PHPUnit\Framework\Attributes\CoversNamespace;

/**
 * A test class declaring #[CoversNamespace], for DeclaredCoverageTest.
 */
#[CoversNamespace('Etel')]
final class DeclaresUnsupported {}
