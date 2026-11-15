<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\MethodComplexityRule;
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
 * @extends RuleTestCase<MethodComplexityRule>
 */
#[CoversClass(MethodComplexityRule::class)]
final class MethodComplexityRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures/PHPStan/Size/';

    private int $limit = 3;

    #[Test]
    #[TestDox('Methods over the limit are reported; those within it and the methods of test classes are not')]
    public function reportsComplexMethods(): void
    {
        $this->analyse(
            files: [self::FIXTURES . 'Measured.php', self::FIXTURES . 'MeasuredTest.php'],
            expectedErrors: [
                [
                    'Method Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured::nested() has cognitive '
                        . 'complexity 12, the limit is 3: split it.',
                    30,
                ],
                [
                    'Method Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured::jumps() has cognitive '
                        . 'complexity 6, the limit is 3: split it.',
                    52,
                ],
            ]
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
        return new MethodComplexityRule(limit: $this->limit, tests: new TestClass(paths: []));
    }
}
