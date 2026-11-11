<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\LineLength;

use Etel\CodingStandard\LineLength\LineLengthGate;
use FilesystemIterator;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function str_repeat;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * @internal
 */
#[CoversClass(LineLengthGate::class)]
final class LineLengthGateTest extends TestCase
{
    private string $root;

    #[Override]
    protected function setUp(): void
    {
        /* @noinspection NonSecureUniqidUsageInspection */
        $this->root = sys_get_temp_dir() . '/etel-line-length-' . uniqid();
        mkdir(directory: $this->root);
    }

    #[Override]
    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator(directory: $this->root, flags: FilesystemIterator::SKIP_DOTS),
            mode: RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $file->isDir() ? rmdir(directory: $file->getPathname()) : unlink(filename: $file->getPathname());
            }
        }

        rmdir(directory: $this->root);
    }

    #[Test]
    #[TestDox('Reports lines over 120 characters, counting characters rather than bytes')]
    public function reportsLongLines(): void
    {
        $this->write(path: 'src/Long.php', content: "<?php\n" . str_repeat(string: 'x', times: 121) . "\n");
        // 120 letters of two bytes each: within the limit
        $this->write(path: 'src/Wide.php', content: "<?php\n" . str_repeat(string: 'ї', times: 120) . "\n");

        $this->assertSame(
            ['long' => ['src/Long.php:2 (121)'], 'orphans' => []],
            new LineLengthGate(root: $this->root)->check()
        );
    }

    #[Test]
    #[TestDox('Reports a docblock followed by another one, blank lines between them or not')]
    public function reportsStrayDocblocks(): void
    {
        $this->write(
            path: 'src/Stray.php',
            content: "<?php\n/**\n * First.\n */\n\n/** Second. */\nfinal class Stray {}\n/** Fine. */\nconst A = 1;\n"
        );

        $this->assertSame(
            ['long' => [], 'orphans' => ['src/Stray.php:4 (docblock followed by another docblock)']],
            new LineLengthGate(root: $this->root)->check()
        );
    }

    #[Test]
    #[TestDox('Checks the default directories, scripts in bin/ whatever their extension, and skips the rest')]
    public function checksTheDefaults(): void
    {
        $long = str_repeat(string: 'x', times: 121) . "\n";
        $this->write(path: 'bin/console', content: $long);
        $this->write(path: 'tests/Long.php', content: $long);
        // not PHP, the generated Symfony reference and a directory outside the defaults
        $this->write(path: 'src/notes.md', content: $long);
        $this->write(path: 'config/reference.php', content: $long);
        $this->write(path: 'other/Long.php', content: $long);

        $this->assertSame(
            ['long' => ['bin/console:1 (121)', 'tests/Long.php:1 (121)'], 'orphans' => []],
            new LineLengthGate(root: $this->root)->check()
        );
    }

    #[Test]
    #[TestDox('Named directories replace the defaults')]
    public function checksNamedDirectories(): void
    {
        $long = str_repeat(string: 'x', times: 121) . "\n";
        $this->write(path: 'src/Long.php', content: $long);
        $this->write(path: 'catalog-migrations/Long.php', content: $long);

        $this->assertSame(
            ['long' => ['catalog-migrations/Long.php:1 (121)'], 'orphans' => []],
            new LineLengthGate(root: $this->root)->check(directories: ['catalog-migrations/'])
        );
    }

    #[Test]
    #[TestDox('A named directory that does not exist is an error, not an empty pass')]
    public function refusesAMissingDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('No directory srcc in ' . $this->root . '.');

        new LineLengthGate(root: $this->root)->check(directories: ['srcc']);
    }

    private function write(string $path, string $content): void
    {
        $file = $this->root . '/' . $path;

        if (!is_dir(filename: dirname(path: $file))) {
            mkdir(directory: dirname(path: $file), recursive: true);
        }

        file_put_contents(filename: $file, data: $content);
    }
}
