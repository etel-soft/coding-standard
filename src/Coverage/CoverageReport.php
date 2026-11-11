<?php

declare(strict_types=1);

namespace Etel\CodingStandard\Coverage;

use function implode;
use function sprintf;
use function strlen;
use function substr;

/**
 * The outcome of a coverage run: the executable lines of the sources against the lines their tests declared and hit.
 */
final readonly class CoverageReport
{
    /**
     * @param int                      $total     Executable lines of the sources
     * @param int                      $covered   Those a declaring test hit
     * @param array<string, list<int>> $uncovered Relative path => the lines no declaring test hit
     */
    public function __construct(
        public int $total,
        public int $covered,
        public array $uncovered
    ) {}

    /**
     * @param string                          $root       Paths in the report are relative to it
     * @param list<string>                    $sources    Absolute paths of the files measured, in report order
     * @param array<string, list<int>>        $executable File => its executable lines (a file absent: no data)
     * @param array<string, array<int, true>> $covered    File => the lines a declaring test hit
     */
    public static function of(string $root, array $sources, array $executable, array $covered): self
    {
        $total = 0;
        $hit = 0;
        $uncovered = [];

        foreach ($sources as $file) {
            foreach ($executable[$file] ?? [] as $line) {
                ++$total;

                if (isset($covered[$file][$line])) {
                    ++$hit;
                } else {
                    $uncovered[substr(string: $file, offset: strlen(string: $root) + 1)][] = $line;
                }
            }
        }

        return new self(total: $total, covered: $hit, uncovered: $uncovered);
    }

    public function percent(): float
    {
        return $this->total === 0 ? 100.0 : 100 * $this->covered / $this->total;
    }

    public function isComplete(): bool
    {
        return $this->uncovered === [];
    }

    /**
     * The summary line and, when something is missed, the uncovered lines file by file.
     */
    public function render(): string
    {
        $text = sprintf("Line coverage: %.2f%% (%d/%d)\n", $this->percent(), $this->covered, $this->total);

        if ($this->uncovered === []) {
            return $text;
        }

        $text .= "Uncovered lines:\n";

        foreach ($this->uncovered as $path => $lines) {
            $text .= '  ' . $path . ': ' . implode(separator: ',', array: $lines) . "\n";
        }

        return $text;
    }
}
