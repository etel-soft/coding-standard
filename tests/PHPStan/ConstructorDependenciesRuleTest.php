<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\ConstructorDependenciesRule;
use Etel\CodingStandard\PHPStan\Size\TestClass;
use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @internal
 *
 * @extends RuleTestCase<ConstructorDependenciesRule>
 */
#[CoversClass(ConstructorDependenciesRule::class)]
final class ConstructorDependenciesRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures/PHPStan/Size/';

    private int $limit = 2;

    #[Test]
    #[TestDox('Object parameters (nullable too) over the limit are reported, outside tests and exempt namespaces')]
    public function reportsTooManyObjects(): void
    {
        $this->analyse(
            files: [
                self::FIXTURES . 'Measured.php',
                self::FIXTURES . 'MeasuredTest.php',
                self::FIXTURES . 'Domain/Model.php',
            ],
            expectedErrors: [[
                'Class Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured takes 3 objects in its constructor, '
                    . 'the limit is 2: split it.',
                13,
            ]]
        );
    }

    #[Test]
    #[TestDox('A limit of 0 turns the rule off')]
    public function turnsOff(): void
    {
        $this->limit = 0;

        $this->analyse(files: [self::FIXTURES . 'Measured.php'], expectedErrors: []);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ConstructorDependenciesRule(
            limit: $this->limit,
            exempt: ['\Domain\\'],
            tests: new TestClass(paths: [])
        );
    }
}
