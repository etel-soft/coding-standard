<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Etel\CodingStandard\PHPStan\Size\CognitiveComplexity;
use Etel\CodingStandard\PHPStan\Size\TestClass;
use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A class's (or enum's) cognitive complexity — the sum of its methods' ({@see CognitiveComplexity})
 * — stays within the limit. Test classes are left out.
 *
 * @implements Rule<InClassNode>
 */
final readonly class ClassComplexityRule implements Rule
{
    /**
     * @param int       $limit The highest complexity allowed (0: the rule is off)
     * @param TestClass $tests Tells test code, which is not checked
     */
    public function __construct(private int $limit, private TestClass $tests) {}

    #[Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getOriginalNode();

        if (
            $this->limit === 0
            || !($class instanceof Class_ || $class instanceof Enum_)
            || $this->tests->is(class: $node->getClassReflection(), file: $scope->getFile())
        ) {
            return [];
        }

        $complexity = CognitiveComplexity::ofClass(class: $class);

        if ($complexity <= $this->limit) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Class ' . $node->getClassReflection()->getName() . ' has cognitive complexity '
                    . $complexity . ', the limit is ' . $this->limit . ': split it.'
            )
                ->identifier(identifier: 'etel.classComplexity')
                ->build(),
        ];
    }
}
