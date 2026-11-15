<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Etel\CodingStandard\PHPStan\Size\TestClass;
use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function count;
use function str_contains;

/**
 * A constructor takes no more objects (parameters typed with a class or interface) than the limit: past it,
 * the class does too much. Test classes are left out, and so are the classes of the namespaces named in
 * `$exempt` (say, domain models, whose constructors take value objects rather than services).
 *
 * @implements Rule<InClassNode>
 */
final readonly class ConstructorDependenciesRule implements Rule
{
    /**
     * @param int          $limit  The most object parameters allowed (0: the rule is off)
     * @param list<string> $exempt Namespace fragments (`\Domain\`) whose classes are not checked
     * @param TestClass    $tests  Tells test code, which is not checked
     */
    public function __construct(
        private int $limit,
        private array $exempt,
        private TestClass $tests
    ) {}

    #[Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->getClassReflection()->getName();
        $constructor = $node->getOriginalNode()->getMethod(name: '__construct');

        if (
            $this->limit === 0
            || $constructor === null
            || $this->exempt(class: $name)
            || $this->tests->is(class: $node->getClassReflection(), file: $scope->getFile())
        ) {
            return [];
        }

        $objects = [];

        foreach ($constructor->params as $param) {
            $type = $param->type instanceof NullableType ? $param->type->type : $param->type;

            if ($type instanceof Name) {
                $objects[] = $param;
            }
        }

        if (count(value: $objects) <= $this->limit) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Class ' . $name . ' takes ' . count(value: $objects) . ' objects in its constructor, '
                    . 'the limit is ' . $this->limit . ': split it.'
            )
                ->identifier(identifier: 'etel.constructorDependencies')
                ->line(line: $constructor->getStartLine())
                ->build(),
        ];
    }

    private function exempt(string $class): bool
    {
        foreach ($this->exempt as $fragment) {
            if (str_contains(haystack: '\\' . $class, needle: $fragment)) {
                return true;
            }
        }

        return false;
    }
}
