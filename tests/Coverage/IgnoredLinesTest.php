<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Coverage;

use Etel\CodingStandard\Coverage\IgnoredLines;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(IgnoredLines::class)]
final class IgnoredLinesTest extends TestCase
{
    #[Test]
    #[TestDox('A trailing marker leaves out its line, one alone on its line the next line of code, a range every line')]
    public function leavesOutTheMarkedLines(): void
    {
        $ignored = IgnoredLines::of(code: <<<'PHP'
            <?php
            $a = 1; // @codeCoverageIgnore the types rule it out
            // an ordinary comment
            // @codeCoverageIgnore: the next line of code, past comments and blank lines
            // still a comment

            $b = 2;
            /* @codeCoverageIgnoreStart nothing calls it
             */
            $c = 3;
            // @codeCoverageIgnoreEnd
            $d = 4;
            /** @codeCoverageIgnore — at the end, with no code after it: nothing */
            PHP);

        $this->assertSame([2, 7, 8, 9, 10, 11], array_keys(array: $ignored->lines));
        $this->assertSame([], $ignored->problems);
    }

    #[Test]
    #[TestDox('A marker with no reason, an End with no Start, a Start in a Start and a Start with no End are problems')]
    public function findsWhatIsWrong(): void
    {
        $ignored = IgnoredLines::of(code: <<<'PHP'
            <?php
            $a = 1; // @codeCoverageIgnore
            // @codeCoverageIgnoreEnd
            // @codeCoverageIgnoreStart */
            // @codeCoverageIgnoreStart again
            $b = 2;
            PHP);

        $this->assertSame([2], array_keys(array: $ignored->lines));
        $this->assertSame(
            [
                '2: @codeCoverageIgnore gives no reason',
                '3: @codeCoverageIgnoreEnd with no Start',
                '4: @codeCoverageIgnoreStart gives no reason',
                '5: @codeCoverageIgnoreStart inside another Start',
                '5: @codeCoverageIgnoreStart with no End',
            ],
            $ignored->problems
        );
    }

    #[Test]
    #[TestDox('A comment that is not valid UTF-8 is a problem, not a marker silently skipped')]
    public function refusesAnUnreadableComment(): void
    {
        $ignored = IgnoredLines::of(code: "<?php\n// @codeCoverageIgnore \xFF broken\n\$a = 1;\n");

        $this->assertSame([], $ignored->lines);
        $this->assertSame(['2: a comment that is not valid UTF-8'], $ignored->problems);
    }
}
