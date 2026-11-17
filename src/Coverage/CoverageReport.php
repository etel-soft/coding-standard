<?php

declare(strict_types=1);

namespace Etel\CodingStandard\Coverage;

use function array_filter;
use function implode;
use function sprintf;
use function strlen;
use function substr;

/**
 * The outcome of a coverage run: the executable lines of the sources against the lines their tests declared and hit,
 * less the lines the sources leave out ({@see IgnoredLines}).
 */
final readonly class CoverageReport
{
    /**
     * @param int                         $total     Executable lines of the sources, those left out not counted
     * @param int                         $covered   Those a declaring test hit
     * @param array<string, list<int>>    $uncovered Relative path => the lines no declaring test hit
     * @param int                         $ignored   Executable lines left out
     * @param array<string, list<string>> $problems  Relative path => what is wrong with its markers ("line: …")
     */
    public function __construct(
        public int $total,
        public int $covered,
        public array $uncovered,
        public int $ignored = 0,
        public array $problems = []
    ) {}

    /**
     * @param string                          $root       Paths in the report are relative to it
     * @param list<string>                    $sources    Absolute paths of the files measured, in report order
     * @param array<string, list<int>>        $executable File => its executable lines (a file absent: no data)
     * @param array<string, array<int, true>> $covered    File => the lines a declaring test hit
     * @param array<string, IgnoredLines>     $ignored    File => the lines it leaves out
     */
    public static function of(
        string $root,
        array $sources,
        array $executable,
        array $covered,
        array $ignored = []
    ): self {
        $total = 0;
        $hit = 0;
        $left = 0;
        $uncovered = [];
        $problems = [];

        foreach ($sources as $file) {
            $path = substr(string: $file, offset: strlen(string: $root) + 1);
            $marked = $ignored[$file] ?? new IgnoredLines(lines: [], problems: []);
            $problems[$path] = $marked->problems;

            foreach ($executable[$file] ?? [] as $line) {
                if (isset($marked->lines[$line])) {
                    ++$left;
                    $problems[$path] = [
                        ...$problems[$path],
                        ...(isset($covered[$file][$line]) ? [$line . ': covered, yet marked @codeCoverageIgnore'] : []),
                    ];

                    continue;
                }

                ++$total;

                if (isset($covered[$file][$line])) {
                    ++$hit;
                } else {
                    $uncovered[$path][] = $line;
                }
            }
        }

        return new self(
            total: $total,
            covered: $hit,
            uncovered: $uncovered,
            ignored: $left,
            problems: array_filter(array: $problems)
        );
    }

    public function percent(): float
    {
        return $this->total === 0 ? 100.0 : 100 * $this->covered / $this->total;
    }

    public function isComplete(): bool
    {
        return $this->uncovered === [] && $this->problems === [];
    }

    /**
     * The summary line and, when something is missed, the uncovered lines file by file, then the markers to fix.
     */
    public function render(): string
    {
        $text = sprintf(
            "Line coverage: %.2f%% (%d/%d)%s\n",
            $this->percent(),
            $this->covered,
            $this->total,
            $this->ignored === 0 ? '' : sprintf(', %d left out', $this->ignored)
        );

        if ($this->uncovered !== []) {
            $text .= "Uncovered lines:\n";

            foreach ($this->uncovered as $path => $lines) {
                $text .= '  ' . $path . ': ' . implode(separator: ',', array: $lines) . "\n";
            }
        }

        if ($this->problems !== []) {
            $text .= "Coverage markers to fix:\n";

            foreach ($this->problems as $path => $problems) {
                foreach ($problems as $problem) {
                    $text .= '  ' . $path . ':' . $problem . "\n";
                }
            }
        }

        return $text;
    }
}
