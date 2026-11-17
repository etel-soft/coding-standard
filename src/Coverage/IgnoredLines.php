<?php

declare(strict_types=1);

namespace Etel\CodingStandard\Coverage;

use PhpToken;

use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_WHITESPACE;

use function array_fill_keys;
use function array_values;
use function preg_match;
use function range;
use function sprintf;
use function substr_count;
use function trim;

/**
 * The lines a source leaves out of the coverage: code no test can reach — a branch the types already rule out,
 * a guard against what cannot happen. Marked in a comment, the reason after the marker:
 *
 *   return $value ?? throw new LogicException(); // @codeCoverageIgnore the parser never yields null here
 *
 *   // @codeCoverageIgnoreStart sodium fails only on a key of the wrong length, which the constructor refuses
 *   ...
 *   // @codeCoverageIgnoreEnd
 *
 * `@codeCoverageIgnore` leaves out the line it ends (a trailing comment) or, alone on its line, the next line of
 * code; Start and End leave out every line from one to the other. A marker with no reason, an End with no Start or
 * a Start with no End is a problem the gate fails on, as is a line left out that a test covers (it is not dead),
 * and so is a comment that is not valid UTF-8 (its markers cannot be read).
 */
final readonly class IgnoredLines
{
    private const string MARKER = '/@codeCoverageIgnore(Start|End)?\b[\s:.,;—–-]*(.*)/su';

    /**
     * @param array<int, true> $lines    The lines left out
     * @param list<string>     $problems "line: what is wrong"
     */
    public function __construct(
        public array $lines,
        public array $problems
    ) {}

    public static function of(string $code): self
    {
        $lines = [];
        [$markers, $problems] = self::markers(code: $code);
        $start = null;

        foreach ($markers as [$kind, $line, $explained, $target]) {
            $problems = [...$problems, ...self::unexplained(kind: $kind, line: $line, explained: $explained)];

            if ($kind === '') {
                $lines += $target === null ? [] : [$target => true];
            } elseif ($kind === 'Start') {
                if ($start !== null) {
                    $problems[] = $line . ': @codeCoverageIgnoreStart inside another Start';
                }

                $start = $line;
            } elseif ($start === null) {
                $problems[] = $line . ': @codeCoverageIgnoreEnd with no Start';
            } else {
                $lines += array_fill_keys(keys: range(start: $start, end: $line), value: true);
                $start = null;
            }
        }

        if ($start !== null) {
            $problems[] = $start . ': @codeCoverageIgnoreStart with no End';
        }

        return new self(lines: $lines, problems: $problems);
    }

    /**
     * The markers of the source, in order: [kind ('', 'Start' or 'End'), line, whether a reason follows,
     * the line a plain marker leaves out (none: no code after it)]; and a problem for each comment not valid UTF-8.
     *
     * @return array{list<array{string, int, bool, ?int}>, list<string>}
     */
    private static function markers(string $code): array
    {
        $tokens = array_values(array: PhpToken::tokenize(code: $code));
        $markers = [];
        $unreadable = [];
        $lastCodeLine = 0;

        foreach ($tokens as $index => $token) {
            if (!$token->is(kind: [T_COMMENT, T_DOC_COMMENT])) {
                $lastCodeLine = $token->is(kind: T_WHITESPACE)
                    ? $lastCodeLine
                    : $token->line + substr_count(haystack: $token->text, needle: "\n");

                continue;
            }

            $found = preg_match(pattern: self::MARKER, subject: $token->text, matches: $marker);

            if ($found === false) {
                $unreadable[] = $token->line . ': a comment that is not valid UTF-8';
            } elseif ($found === 1) {
                $markers[] = [
                    $marker[1],
                    $token->line,
                    trim(string: $marker[2], characters: " \t\n\r*/") !== '',
                    $lastCodeLine === $token->line ? $token->line : self::nextCodeLine(tokens: $tokens, after: $index),
                ];
            }
        }

        return [$markers, $unreadable];
    }

    /**
     * The problem of a marker with no reason after it, if it is one; an End needs none.
     *
     * @return list<string>
     */
    private static function unexplained(string $kind, int $line, bool $explained): array
    {
        return $kind === 'End' || $explained
            ? []
            : [sprintf('%d: @codeCoverageIgnore%s gives no reason', $line, $kind)];
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private static function nextCodeLine(array $tokens, int $after): ?int
    {
        for ($index = $after + 1; isset($tokens[$index]); ++$index) {
            if (!$tokens[$index]->is(kind: [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return $tokens[$index]->line;
            }
        }

        return null;
    }
}
