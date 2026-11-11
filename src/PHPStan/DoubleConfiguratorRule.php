<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function in_array;
use function strtolower;

/**
 * Test doubles come from the etel/phpunit-double configurators (`createStubConfig()`, `createMockConfig()`),
 * not from PHPUnit's own factories: one fluent, sealed set-up per double.
 *
 * @implements Rule<CallLike>
 */
final readonly class DoubleConfiguratorRule implements Rule
{
    private const array FACTORIES = [
        'createconfiguredmock',
        'createconfiguredstub',
        'createmock',
        'createmockforintersectionofinterfaces',
        'createpartialmock',
        'createstub',
        'createstubforintersectionofinterfaces',
        'getmockbuilder',
    ];

    #[Override]
    public function getNodeType(): string
    {
        return CallLike::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ((!$node instanceof MethodCall && !$node instanceof StaticCall) || !$node->name instanceof Identifier) {
            return [];
        }

        $name = $node->name->toString();

        if (!in_array(needle: strtolower(string: $name), haystack: self::FACTORIES, strict: true)) {
            return [];
        }

        $class = $scope->getClassReflection();

        if ($class === null || !$class->is(className: 'PHPUnit\Framework\TestCase')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Create the double with createStubConfig()/createMockConfig() instead of ' . $name . '().'
            )
                ->identifier(identifier: 'etel.doubleConfigurator')
                ->build(),
        ];
    }
}
