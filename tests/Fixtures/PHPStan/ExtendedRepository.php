<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\PHPStan;

use Doctrine\Persistence\ObjectRepository;
use stdClass;

/**
 * @extends ObjectRepository<stdClass>
 */
interface ExtendedRepository extends ObjectRepository {}
