<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * No variables inside strings (`"a {$b} c"`, heredocs alike): strings are built by concatenation (`'a ' . $b . ' c'`),
 * so every value in a string is an expression that reads as one.
 *
 * @implements Rule<InterpolatedString>
 */
final readonly class NoStringInterpolationRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return InterpolatedString::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return [
            RuleErrorBuilder::message(message: 'Build the string by concatenation instead of interpolating values.')
                ->identifier(identifier: 'etel.stringInterpolation')
                ->build(),
        ];
    }
}
