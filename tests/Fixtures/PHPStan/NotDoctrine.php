<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\PHPStan;

final class NotDoctrine
{
    public function getRepository(): string
    {
        return 'not Doctrine';
    }
}
