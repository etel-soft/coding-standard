<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\NoOrmRepositoryClassRule;
use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @internal
 *
 * @extends RuleTestCase<NoOrmRepositoryClassRule>
 */
#[CoversClass(NoOrmRepositoryClassRule::class)]
final class NoOrmRepositoryClassRuleTest extends RuleTestCase
{
    #[Test]
    #[TestDox('A class implementing ObjectRepository is reported; an interface extending it and other classes are not')]
    public function reportsRepositoryClasses(): void
    {
        $this->analyse(
            files: [
                __DIR__ . '/../Fixtures/PHPStan/StdClassRepository.php',
                __DIR__ . '/../Fixtures/PHPStan/ExtendedRepository.php',
                __DIR__ . '/../Fixtures/PHPStan/NotDoctrine.php',
            ],
            expectedErrors: [[
                'Class Etel\CodingStandardTests\Fixtures\PHPStan\StdClassRepository is a Doctrine repository: read '
                    . 'through queries instead.',
                13,
            ]]
        );
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new NoOrmRepositoryClassRule();
    }
}
