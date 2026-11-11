<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Coverage;

use Etel\CodingStandard\Coverage\DeclaredCoverageExtension;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function fclose;
use function getenv;
use function is_resource;
use function proc_close;
use function proc_open;
use function stream_get_contents;

/**
 * bin/etel-coverage end to end, on the fixture project in tests/Fixtures/Coverage: the extension only works inside a
 * PHPUnit run of its own, with pcov collecting.
 *
 * @internal
 */
#[CoversClass(DeclaredCoverageExtension::class)]
final class DeclaredCoverageExtensionTest extends TestCase
{
    private const string BIN = __DIR__ . '/../../bin/etel-coverage';

    private const string PROJECT = __DIR__ . '/../Fixtures/Coverage';

    #[Override]
    protected function setUp(): void
    {
        if (!extension_loaded(extension: 'pcov')) {
            $this->markTestSkipped('pcov is not loaded.');
        }
    }

    #[Test]
    #[TestDox('Counts what the test declares — the class and its trait — and not the helper it merely runs')]
    public function countsDeclaredLinesOnly(): void
    {
        [$exit, $output] = self::coverage(environment: []);

        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString(
            'OK (1 test, 2 assertions)',
            $output,
            'the fixture run is clean'
        );
        $this->assertStringContainsString("Line coverage: 60.00% (3/5)\nUncovered lines:\n", $output);
        $this->assertStringContainsString('  Src/Helper.php: 14', $output);
        $this->assertStringContainsString('  Src/functions.php: 9', $output);
        $this->assertStringNotContainsString('Src/Calculator.php', $output);
        $this->assertStringNotContainsString('Src/Rounding.php', $output);
    }

    #[Test]
    #[TestDox('Passes when every line in scope is covered')]
    public function passesInScope(): void
    {
        [$exit, $output] = self::coverage(environment: ['COVERAGE_SCOPE' => 'Src/Calculator.php,Src/Rounding.php']);

        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('Line coverage: 100.00% (3/3)', $output);
    }

    #[Test]
    #[TestDox('A source directory that does not exist is an error')]
    public function refusesAMissingSourceDirectory(): void
    {
        [$exit, $output] = self::coverage(environment: ['COVERAGE_SOURCES' => 'lib']);

        $this->assertSame(2, $exit);
        $this->assertStringContainsString('No directory lib in ', $output);
    }

    /**
     * @param array<string, string> $environment
     *
     * @return array{int, string} The exit code and what the run wrote (standard output, then errors)
     */
    private static function coverage(array $environment): array
    {
        $process = proc_open(
            command: [PHP_BINARY, '-d', 'pcov.enabled=1', '-d', 'pcov.directory=.', self::BIN],
            descriptor_spec: [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            pipes: $pipes,
            cwd: self::PROJECT,
            env_vars: [...getenv(), 'COVERAGE_SOURCES' => 'Src', ...$environment]
        );

        if (!is_resource(value: $process)) {
            self::fail('bin/etel-coverage did not start.');
        }

        // both are short: reading one to its end before the other cannot fill a pipe and stall the run
        $output = stream_get_contents(stream: $pipes[1]) . stream_get_contents(stream: $pipes[2]);
        fclose(stream: $pipes[1]);
        fclose(stream: $pipes[2]);

        return [proc_close(process: $process), $output];
    }
}
