<?php

declare(strict_types=1);

namespace Etel\CodingStandard\LineLength;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file;
use function in_array;
use function is_dir;
use function mb_strlen;
use function rtrim;
use function sort;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

/**
 * No line of the PHP sources is longer than 120 characters (php-cs-fixer has no such rule), and no docblock is followed
 * by another one (blank lines between do not count): the first describes nothing — usually code inserted between a
 * docblock and what it documented.
 *
 * Characters, not bytes: Ukrainian or other non-ASCII text counts once per letter. Scripts in bin/ are checked whatever
 * their extension (bin/console has none); the generated config/reference.php is left out.
 */
final readonly class LineLengthGate
{
    public const int LIMIT = 120;

    /** What a Symfony project keeps its PHP in: checked when no directory is named */
    public const array DEFAULT_DIRECTORIES = ['bin', 'config', 'migrations', 'public', 'src', 'tests', 'tools'];

    /** Symfony generates it on every cache warmup */
    private const array SKIPPED = ['config/reference.php'];

    /**
     * @param string $root The package root: directories and reported paths are relative to it
     */
    public function __construct(
        private string $root
    ) {}

    /**
     * @param list<string> $directories Relative to the root; none — the defaults, those missing skipped. A directory
     *                                  named explicitly must exist: a typo would otherwise pass as "nothing too long".
     *
     * @throws InvalidArgumentException                         When a named directory does not exist
     * @return array{long: list<string>, orphans: list<string>} "path:line (length)" and "path:line (…)" entries, sorted
     */
    public function check(array $directories = []): array
    {
        $explicit = $directories !== [];
        $long = [];
        $orphans = [];

        foreach ($explicit ? $directories : self::DEFAULT_DIRECTORIES as $directory) {
            $directory = rtrim(string: $directory, characters: '/');

            if (!is_dir(filename: $this->root . '/' . $directory)) {
                if ($explicit) {
                    throw new InvalidArgumentException(
                        message: 'No directory ' . $directory . ' in ' . $this->root . '.'
                    );
                }

                continue;
            }

            $files = new RecursiveIteratorIterator(
                iterator: new RecursiveDirectoryIterator(
                    directory: $this->root . '/' . $directory,
                    flags: FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($files as $file) {
                if ($file instanceof SplFileInfo) {
                    $this->checkFile(file: $file, long: $long, orphans: $orphans);
                }
            }
        }

        sort(array: $long);
        sort(array: $orphans);

        return ['long' => $long, 'orphans' => $orphans];
    }

    /**
     * @param list<string> $long
     * @param list<string> $orphans
     */
    private function checkFile(SplFileInfo $file, array &$long, array &$orphans): void
    {
        $path = substr(string: $file->getPathname(), offset: strlen(string: $this->root) + 1);
        $skipped = in_array(needle: $path, haystack: self::SKIPPED, strict: true);
        $script = str_ends_with(haystack: $path, needle: '.php') || str_starts_with(haystack: $path, needle: 'bin/');

        if ($skipped || !$script) {
            return;
        }

        $closedAt = null;
        $inDocblock = false;

        foreach (file(filename: $file->getPathname(), flags: FILE_IGNORE_NEW_LINES) ?: [] as $index => $line) {
            $length = mb_strlen(string: rtrim(string: $line, characters: "\r"), encoding: 'UTF-8');

            if ($length > self::LIMIT) {
                $long[] = $path . ':' . ($index + 1) . ' (' . $length . ')';
            }

            $trimmed = trim(string: $line);

            if ($inDocblock) {
                $inDocblock = !str_ends_with(haystack: $trimmed, needle: '*/');
                $closedAt = $inDocblock ? null : $index + 1;
            } elseif (str_starts_with(haystack: $trimmed, needle: '/**')) {
                if ($closedAt !== null) {
                    $orphans[] = $path . ':' . $closedAt . ' (docblock followed by another docblock)';
                }

                $inDocblock = !str_ends_with(haystack: $trimmed, needle: '*/');
                $closedAt = $inDocblock ? null : $index + 1;
            } elseif ($trimmed !== '') {
                $closedAt = null;
            }
        }
    }
}
