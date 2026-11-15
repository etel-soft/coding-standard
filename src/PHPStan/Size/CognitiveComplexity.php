<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan\Size;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;

use function in_array;
use function is_array;

/**
 * Cognitive complexity (G. Ann Campbell, SonarSource), counted the way tomasvotruba/cognitive-complexity does:
 *
 * - +1 for each if / elseif / else, switch, match, loop, catch, ternary, goto and break/continue with a level;
 * - plus the nesting level for each of those that nests (not elseif / else, they sit on their if's level);
 * - closures and arrow functions raise the nesting level of what they hold without counting themselves;
 * - +1 for each run of the same boolean operator (`a && b && c` is 1, `a && b || c` is 2).
 *
 * A class's complexity is the sum of its methods'.
 */
final readonly class CognitiveComplexity
{
    /** Count +1 */
    private const array COUNTED = [
        If_::class,
        ElseIf_::class,
        Else_::class,
        Switch_::class,
        Match_::class,
        For_::class,
        Foreach_::class,
        While_::class,
        Do_::class,
        Catch_::class,
        Ternary::class,
    ];

    /** Raise the nesting level of what they hold */
    private const array NESTING = [
        If_::class,
        Switch_::class,
        Match_::class,
        For_::class,
        Foreach_::class,
        While_::class,
        Do_::class,
        Catch_::class,
        Ternary::class,
        Closure::class,
        ArrowFunction::class,
    ];

    private const array BOOLEAN = [BooleanAnd::class, BooleanOr::class, LogicalAnd::class, LogicalOr::class];

    public static function ofClass(ClassLike $class): int
    {
        $total = 0;

        foreach ($class->getMethods() as $method) {
            $total += self::of(node: $method);
        }

        return $total;
    }

    public static function of(Node $node): int
    {
        return self::walk(node: $node, nesting: 0, operator: null);
    }

    /**
     * @param ?string $operator The boolean operator of the parent when this node is one of its operands
     */
    private static function walk(Node $node, int $nesting, ?string $operator): int
    {
        $nests = in_array(needle: $node::class, haystack: self::NESTING, strict: true);
        $boolean = in_array(needle: $node::class, haystack: self::BOOLEAN, strict: true);
        $score = self::own(node: $node, nesting: $nesting, operator: $operator);

        foreach (self::children(node: $node) as $child) {
            $score += self::walk(
                node: $child,
                nesting: $nests ? $nesting + 1 : $nesting,
                operator: $boolean ? $node::class : null
            );
        }

        return $score;
    }

    /**
     * What the node adds by itself, without its children.
     */
    private static function own(Node $node, int $nesting, ?string $operator): int
    {
        $class = $node::class;

        if (in_array(needle: $class, haystack: self::BOOLEAN, strict: true)) {
            return $class === $operator ? 0 : 1;
        }

        if (!in_array(needle: $class, haystack: self::COUNTED, strict: true) && !self::jumps(node: $node)) {
            return 0;
        }

        return in_array(needle: $class, haystack: self::NESTING, strict: true) ? 1 + $nesting : 1;
    }

    /**
     * @return list<Node>
     */
    private static function children(Node $node): array
    {
        $children = [];

        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->{$name};

            foreach (is_array(value: $value) ? $value : [$value] as $child) {
                if ($child instanceof Node) {
                    $children[] = $child;
                }
            }
        }

        return $children;
    }

    /**
     * goto, and break / continue that leave more than the innermost loop.
     */
    private static function jumps(Node $node): bool
    {
        return $node instanceof Goto_
            || ($node instanceof Break_ && $node->num !== null)
            || ($node instanceof Continue_ && $node->num !== null);
    }
}
