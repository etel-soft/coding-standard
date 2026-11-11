<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Coverage;

use Etel\CodingStandard\Coverage\DeclaredCoverage;
use Etel\CodingStandardTests\Fixtures\Coverage\Declares\DeclaresClass;
use Etel\CodingStandardTests\Fixtures\Coverage\Declares\DeclaresClassAndMethod;
use Etel\CodingStandardTests\Fixtures\Coverage\Declares\DeclaresNothing;
use Etel\CodingStandardTests\Fixtures\Coverage\Declares\DeclaresRanges;
use Etel\CodingStandardTests\Fixtures\Coverage\Declares\DeclaresTrait;
use Etel\CodingStandardTests\Fixtures\Coverage\Declares\DeclaresUnsupported;
use Etel\CodingStandardTests\Fixtures\Coverage\Src\Calculator;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

use function realpath;

/**
 * @internal
 */
#[CoversClass(DeclaredCoverage::class)]
final class DeclaredCoverageTest extends TestCase
{
    private const string SOURCES = __DIR__ . '/../Fixtures/Coverage/Src/';

    #[Override]
    public static function setUpBeforeClass(): void
    {
        // a function has no autoloading
        require_once self::SOURCES . 'functions.php';
    }

    #[Test]
    #[TestDox('A covered class is its whole file and the files of the traits it uses')]
    public function coversAClass(): void
    {
        $this->assertSame(
            [self::source(name: 'Calculator') => null, self::source(name: 'Rounding') => null],
            DeclaredCoverage::of(testClass: DeclaresClass::class)
        );
    }

    #[Test]
    #[TestDox('A covered trait is its whole file')]
    public function coversATrait(): void
    {
        $this->assertSame(
            [self::source(name: 'Rounding') => null],
            DeclaredCoverage::of(testClass: DeclaresTrait::class)
        );
    }

    #[Test]
    #[TestDox('Covered methods and functions are their line ranges; attributes not about coverage are ignored')]
    public function coversRanges(): void
    {
        $add = new ReflectionMethod(objectOrMethod: Calculator::class, method: 'add');
        $half = new ReflectionMethod(objectOrMethod: Calculator::class, method: 'half');
        $double = new ReflectionFunction(function: 'Etel\CodingStandardTests\Fixtures\Coverage\Src\double');

        $this->assertSame(
            [
                self::source(name: 'Calculator') => [
                    [$add->getStartLine(), $add->getEndLine()],
                    [$half->getStartLine(), $half->getEndLine()],
                ],
                self::source(name: 'functions') => [[$double->getStartLine(), $double->getEndLine()]],
            ],
            DeclaredCoverage::of(testClass: DeclaresRanges::class)
        );
    }

    #[Test]
    #[TestDox('A method of a class already covered whole adds nothing')]
    public function classWinsOverItsMethod(): void
    {
        $this->assertSame(
            [self::source(name: 'Calculator') => null, self::source(name: 'Rounding') => null],
            DeclaredCoverage::of(testClass: DeclaresClassAndMethod::class)
        );
    }

    #[Test]
    #[TestDox('CoversNothing declares nothing')]
    public function coversNothing(): void
    {
        $this->assertSame([], DeclaredCoverage::of(testClass: DeclaresNothing::class));
    }

    #[Test]
    #[TestDox('A Covers attribute the gate cannot count is refused')]
    public function refusesUnsupported(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs(
            DeclaresUnsupported::class
            . ': #[PHPUnit\Framework\Attributes\CoversNamespace] is not supported by etel-coverage.'
        );

        DeclaredCoverage::of(testClass: DeclaresUnsupported::class);
    }

    #[Test]
    #[TestDox('A line is declared when its file is covered whole or it lies in one of the ranges')]
    public function declaresLines(): void
    {
        $this->assertTrue(DeclaredCoverage::declares(ranges: null, line: 7));
        $this->assertTrue(DeclaredCoverage::declares(ranges: [[1, 3], [10, 12]], line: 10));
        $this->assertTrue(DeclaredCoverage::declares(ranges: [[1, 3], [10, 12]], line: 3));
        $this->assertFalse(DeclaredCoverage::declares(ranges: [[1, 3], [10, 12]], line: 4));
        $this->assertFalse(DeclaredCoverage::declares(ranges: [], line: 1));
    }

    private static function source(string $name): string
    {
        return (string) realpath(path: self::SOURCES . $name . '.php');
    }
}
