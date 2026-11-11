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
 * XML goes through the project's wrapper (the `wrapper` argument: no warnings on malformed input, no network access),
 * never through the raw simplexml_load_*() functions.
 *
 * @implements Rule<FuncCall>
 */
final readonly class NoRawXmlRule implements Rule
{
    private const array FUNCTIONS = ['simplexml_load_string', 'simplexml_load_file'];

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
                message: 'Use ' . ($this->wrapper ?? 'the project’s XML wrapper') . ' instead of ' . $function . '().'
            )
                ->identifier(identifier: 'etel.rawXml')
                ->build(),
        ];
    }
}
