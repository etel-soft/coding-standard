<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Coverage;

use Etel\CodingStandard\Coverage\CoverageReport;
use Etel\CodingStandard\Coverage\IgnoredLines;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CoverageReport::class)]
final class CoverageReportTest extends TestCase
{
    #[Test]
    #[TestDox('Counts the executable lines against the covered ones and lists the rest file by file')]
    public function reportsUncoveredLines(): void
    {
        $report = CoverageReport::of(
            root: '/app',
            sources: ['/app/src/A.php', '/app/src/B.php', '/app/src/C.php', '/app/src/NoData.php'],
            executable: ['/app/src/A.php' => [3, 4, 5], '/app/src/B.php' => [7], '/app/src/C.php' => [2, 9]],
            covered: ['/app/src/A.php' => [3 => true, 5 => true], '/app/src/B.php' => [7 => true]]
        );

        $this->assertSame(6, $report->total);
        $this->assertSame(3, $report->covered);
        $this->assertSame(['src/A.php' => [4], 'src/C.php' => [2, 9]], $report->uncovered);
        $this->assertSame(50.0, $report->percent());
        $this->assertFalse($report->isComplete());
        $this->assertSame(
            "Line coverage: 50.00% (3/6)\nUncovered lines:\n  src/A.php: 4\n  src/C.php: 2,9\n",
            $report->render()
        );
    }

    #[Test]
    #[TestDox('Everything covered is complete; covered lines outside the sources do not count')]
    public function isCompleteWhenEverythingIsCovered(): void
    {
        $report = CoverageReport::of(
            root: '/app',
            sources: ['/app/src/A.php'],
            executable: ['/app/src/A.php' => [3]],
            covered: ['/app/src/A.php' => [3 => true], '/app/src/Other.php' => [1 => true]]
        );

        $this->assertTrue($report->isComplete());
        $this->assertSame("Line coverage: 100.00% (1/1)\n", $report->render());
    }

    #[Test]
    #[TestDox('Lines left out are not counted; one a test covers, and a marker gone wrong, are to fix')]
    public function leavesOutIgnoredLines(): void
    {
        $sources = ['/app/src/A.php', '/app/src/B.php'];
        $executable = ['/app/src/A.php' => [3, 4, 5], '/app/src/B.php' => [7]];

        $clean = CoverageReport::of(
            root: '/app',
            sources: $sources,
            executable: $executable,
            covered: ['/app/src/A.php' => [3 => true], '/app/src/B.php' => [7 => true]],
            ignored: ['/app/src/A.php' => new IgnoredLines(lines: [4 => true, 5 => true, 6 => true], problems: [])]
        );
        $wrong = CoverageReport::of(
            root: '/app',
            sources: $sources,
            executable: $executable,
            covered: ['/app/src/A.php' => [3 => true, 4 => true]],
            ignored: [
                '/app/src/A.php' => new IgnoredLines(lines: [4 => true], problems: []),
                '/app/src/B.php' => new IgnoredLines(lines: [], problems: ['9: @codeCoverageIgnoreEnd with no Start']),
            ]
        );

        $this->assertSame([2, 2, 2], [$clean->total, $clean->covered, $clean->ignored]);
        $this->assertTrue($clean->isComplete());
        $this->assertSame("Line coverage: 100.00% (2/2), 2 left out\n", $clean->render());
        $this->assertFalse($wrong->isComplete());
        $this->assertSame(
            <<<'TEXT'
                Line coverage: 33.33% (1/3), 1 left out
                Uncovered lines:
                  src/A.php: 5
                  src/B.php: 7
                Coverage markers to fix:
                  src/A.php:4: covered, yet marked @codeCoverageIgnore
                  src/B.php:9: @codeCoverageIgnoreEnd with no Start

                TEXT,
            $wrong->render()
        );
    }

    #[Test]
    #[TestDox('No executable line at all is full coverage')]
    public function emptyIsComplete(): void
    {
        $report = CoverageReport::of(root: '/app', sources: [], executable: [], covered: []);

        $this->assertSame(100.0, $report->percent());
        $this->assertTrue($report->isComplete());
    }
}
