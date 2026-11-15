<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\ClassComplexityRule;
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
 * @extends RuleTestCase<ClassComplexityRule>
 */
#[CoversClass(ClassComplexityRule::class)]
final class ClassComplexityRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures/PHPStan/Size/';

    private int $limit = 15;

    #[Test]
    #[TestDox('A class over the limit is reported; test classes and classes within it are not')]
    public function reportsComplexClasses(): void
    {
        $this->analyse(
            files: [
                self::FIXTURES . 'Measured.php',
                self::FIXTURES . 'MeasuredTest.php',
                self::FIXTURES . 'Domain/Model.php',
            ],
            expectedErrors: [[
                'Class Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured has cognitive complexity 18, '
                    . 'the limit is 15: split it.',
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
        return new ClassComplexityRule(limit: $this->limit, tests: new TestClass(paths: []));
    }
}
