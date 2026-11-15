<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan;

use Etel\CodingStandard\PHPStan\NoOrmRepositoryRule;
use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @internal
 *
 * @extends RuleTestCase<NoOrmRepositoryRule>
 */
#[CoversClass(NoOrmRepositoryRule::class)]
final class NoOrmRepositoryRuleTest extends RuleTestCase
{
    private const string MESSAGE = 'Read through a query, not getRepository(); a command handler loads the row it '
        . 'changes with find() by id or changes rows with a DQL statement.';

    #[Test]
    #[TestDox('getRepository() on an object manager or a manager registry is reported, find() and others are not')]
    public function reportsGetRepository(): void
    {
        $this->analyse(
            files: [__DIR__ . '/../Fixtures/PHPStan/RepositoryCalls.php'],
            expectedErrors: [[self::MESSAGE, 21], [self::MESSAGE, 22]]
        );
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new NoOrmRepositoryRule();
    }
}
