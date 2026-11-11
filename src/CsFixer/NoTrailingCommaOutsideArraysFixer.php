<?php

declare(strict_types=1);

namespace Etel\CodingStandard\CsFixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

use const T_ARRAY;
use const T_LIST;
use const T_MATCH;

/**
 * The project rule: a trailing comma after the last element belongs to arrays only.
 *
 * Arrays (and array destructuring, which is the same syntax) keep theirs — `trailing_comma_in_multiline`
 * restricted to those elements adds it. Everywhere else a list ends without one: call and `new`
 * arguments, parameter lists, closure `use` lists, attribute arguments, and `match` arms.
 *
 * php-cs-fixer has no rule that removes a trailing comma from a multi-line list — the built-in ones
 * only add it, or remove it on a single line — hence this one.
 */
final class NoTrailingCommaOutsideArraysFixer implements FixerInterface
{
    public const string NAME = 'Etel/no_trailing_comma_outside_arrays';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'A trailing comma after the last element belongs to arrays: never to arguments, parameters or `match`.',
            [
                new CodeSample("<?php\nfoo(\n    \$a,\n    \$b,\n);\n"),
                new CodeSample("<?php\n\$x = match (\$y) {\n    1 => 'a',\n    default => 'b',\n};\n"),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(',');
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        // After everything that may add or move commas; nothing runs after this that cares.
        return -100;
    }

    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if (!$tokens[$index]->equals(',')) {
                continue;
            }

            $next = $tokens->getNextMeaningfulToken($index);

            if ($next === null) {
                continue;
            }

            if ($tokens[$next]->equals(')') && !$this->closesArrayOrList(tokens: $tokens, close: $next)) {
                $tokens->clearAt($index);
            } elseif ($tokens[$next]->equals('}') && $this->closesMatch(tokens: $tokens, close: $next)) {
                $tokens->clearAt($index);
            }
        }
    }

    /**
     * `array(...)` and `list(...)`: the two parenthesised forms that are arrays, not argument lists.
     */
    private function closesArrayOrList(Tokens $tokens, int $close): bool
    {
        $open = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS, $close);
        $before = $tokens->getPrevMeaningfulToken($open);

        return $before !== null && $tokens[$before]->isGivenKind([T_ARRAY, T_LIST]);
    }

    private function closesMatch(Tokens $tokens, int $close): bool
    {
        $open = $tokens->findBlockStart(Tokens::BLOCK_TYPE_BRACE, $close);
        $subjectClose = $tokens->getPrevMeaningfulToken($open);

        if ($subjectClose === null || !$tokens[$subjectClose]->equals(')')) {
            return false;
        }

        $subjectOpen = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS, $subjectClose);
        $keyword = $tokens->getPrevMeaningfulToken($subjectOpen);

        return $keyword !== null && $tokens[$keyword]->isGivenKind(T_MATCH);
    }
}
