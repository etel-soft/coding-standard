<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Etel\CodingStandard\PHPStan\Size\CognitiveComplexity;
use Etel\CodingStandard\PHPStan\Size\TestClass;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A method's cognitive complexity ({@see CognitiveComplexity}) stays within the limit. Test classes are left out.
 *
 * @implements Rule<InClassMethodNode>
 */
final readonly class MethodComplexityRule implements Rule
{
    /**
     * @param int       $limit The highest complexity allowed (0: the rule is off)
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

        $complexity = CognitiveComplexity::of(node: $node->getOriginalNode());

        if ($complexity <= $this->limit) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Method ' . $node->getClassReflection()->getName() . '::' . $node->getOriginalNode()->name
                    . '() has cognitive complexity ' . $complexity . ', the limit is ' . $this->limit
                    . ': split it.'
            )
                ->identifier(identifier: 'etel.methodComplexity')
                ->build(),
        ];
    }
}
