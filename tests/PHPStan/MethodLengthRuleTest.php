<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\MethodLengthRule;
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
 * @extends RuleTestCase<MethodLengthRule>
 */
#[CoversClass(MethodLengthRule::class)]
final class MethodLengthRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures/PHPStan/Size/';

    private int $limit = 10;

    #[Test]
    #[TestDox('Methods longer than the limit (docblock not counted) are reported; methods of test classes are not')]
    public function reportsLongMethods(): void
    {
        $this->analyse(
            files: [self::FIXTURES . 'Measured.php', self::FIXTURES . 'MeasuredTest.php'],
            expectedErrors: [[
                'Method Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured::nested() is 18 lines long, '
                    . 'the limit is 10: split it.',
                32,
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
        return new MethodLengthRule(limit: $this->limit, tests: new TestClass(paths: []));
    }
}
