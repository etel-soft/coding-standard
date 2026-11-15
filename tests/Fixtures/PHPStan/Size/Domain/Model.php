<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\PHPStan\Size\Domain;

use DateTimeImmutable;
use stdClass;

/**
 * A domain model: its constructor takes value objects, so the dependency limit does not apply to it.
 */
final readonly class Model
{
    public function __construct(
        public stdClass $first,
        public stdClass $second,
        public DateTimeImmutable $third
    ) {}
}
