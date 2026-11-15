<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Etel\CodingStandard\PHPStan\Size\TestClass;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A method spans no more lines than the limit, from its first attribute or keyword to its closing brace
 * (the docblock is not counted). Test classes are left out.
 *
 * @implements Rule<InClassMethodNode>
 */
final readonly class MethodLengthRule implements Rule
{
    /**
     * @param int       $limit The most lines allowed (0: the rule is off)
     * @param TestClass $tests Tells test code, which is not checked
     */
    public function __construct(private int $limit, private TestClass $tests) {}

    #[Override]
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->limit === 0 || $this->tests->is(class: $node->getClassReflection(), file: $scope->getFile())) {
            return [];
        }

        $method = $node->getOriginalNode();
        $lines = $method->getEndLine() - $method->getStartLine() + 1;

        if ($lines <= $this->limit) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Method ' . $node->getClassReflection()->getName() . '::' . $method->name . '() is '
                    . $lines . ' lines long, the limit is ' . $this->limit . ': split it.'
            )
                ->identifier(identifier: 'etel.methodLength')
                ->build(),
        ];
    }
}
