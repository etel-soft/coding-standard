<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Etel\CodingStandard\PHPStan\Size\TestClass;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A class (enum, trait, interface) spans no more lines than the limit, from its first attribute or keyword to its
 * closing brace (the docblock is not counted). Test classes have a limit of their own.
 *
 * @implements Rule<InClassNode>
 */
final readonly class ClassLengthRule implements Rule
{
    /**
     * @param int       $limit     The most lines allowed (0: no limit)
     * @param int       $testLimit The same for test code (0: no limit)
     * @param TestClass $tests     Tells test code
     */
    public function __construct(
        private int $limit,
        private int $testLimit,
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
        $test = $this->tests->is(class: $node->getClassReflection(), file: $scope->getFile());
        $limit = $test ? $this->testLimit : $this->limit;
        $class = $node->getOriginalNode();
        $lines = $class->getEndLine() - $class->getStartLine() + 1;

        if ($limit === 0 || $lines <= $limit) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Class ' . $node->getClassReflection()->getName() . ' is ' . $lines
                    . ' lines long, the limit is ' . $limit . ': split it.'
            )
                ->identifier(identifier: 'etel.classLength')
                ->build(),
        ];
    }
}
