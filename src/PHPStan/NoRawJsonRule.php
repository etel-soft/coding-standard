<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function in_array;
use function strtolower;

/**
 * JSON goes through the project's wrapper (the `wrapper` argument: errors throw, one set of encoding flags),
 * never through the raw json_encode()/json_decode(), whose defaults return null/false on malformed input.
 *
 * @implements Rule<FuncCall>
 */
final readonly class NoRawJsonRule implements Rule
{
    private const array FUNCTIONS = ['json_decode', 'json_encode'];

    /**
     * @param null|class-string $wrapper the one class allowed to call the raw functions (`wrapper` in the
     *                                   `etel` parameters); null until the project has one
     */
    public function __construct(
        private ?string $wrapper
    ) {}

    #[Override]
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $function = strtolower(string: $node->name->getLast());

        if (!in_array(needle: $function, haystack: self::FUNCTIONS, strict: true)) {
            return [];
        }

        if ($this->wrapper !== null && $scope->getClassReflection()?->getName() === $this->wrapper) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Use ' . ($this->wrapper ?? 'the project’s JSON wrapper') . ' instead of ' . $function . '().'
            )
                ->identifier(identifier: 'etel.rawJson')
                ->build(),
        ];
    }
}
