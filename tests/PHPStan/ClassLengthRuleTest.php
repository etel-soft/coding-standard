<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\ClassLengthRule;
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
 * @extends RuleTestCase<ClassLengthRule>
 */
#[CoversClass(ClassLengthRule::class)]
final class ClassLengthRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures/PHPStan/Size/';

    private int $limit = 20;

    private int $testLimit = 10;

    #[Test]
    #[TestDox('Classes longer than their limit are reported: test classes against the test limit')]
    public function reportsLongClasses(): void
    {
        $this->analyse(
            files: [
                self::FIXTURES . 'Measured.php',
                self::FIXTURES . 'MeasuredTest.php',
                self::FIXTURES . 'Domain/Model.php',
            ],
            expectedErrors: [
                [
                    'Class Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured is 52 lines long, the limit is '
                        . '20: split it.',
                    11,
                ],
                [
                    'Class Etel\CodingStandardTests\Fixtures\PHPStan\Size\MeasuredTest is 18 lines long, the '
                        . 'limit is 10: split it.',
                    16,
                ],
            ]
        );
    }

    #[Test]
    #[TestDox('A limit of 0 lifts the limit')]
    public function turnsOff(): void
    {
        $this->limit = 0;
        $this->testLimit = 0;

        $this->analyse(
            files: [self::FIXTURES . 'Measured.php', self::FIXTURES . 'MeasuredTest.php'],
            expectedErrors: []
        );
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ClassLengthRule(
            limit: $this->limit,
            testLimit: $this->testLimit,
            tests: new TestClass(paths: [])
        );
    }
}
