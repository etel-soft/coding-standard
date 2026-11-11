<?php

declare(strict_types=1);

namespace Etel\CodingStandard\Coverage;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\CoversTrait;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

use function array_key_exists;
use function str_starts_with;

/**
 * What a test class declares it covers, as files and the line ranges in them (null: the whole file)
 * — PHPUnit's own semantics of the attributes:
 *
 *   #[CoversClass(X)]     the lines of X's file and of every trait X uses (recursively)
 *   #[CoversTrait(T)]     the lines of T's file and of the traits T uses
 *   #[CoversMethod(X, m)] the lines of that method
 *   #[CoversFunction(f)]  the lines of that function
 *   #[CoversNothing]      nothing
 *
 * Any other Covers* attribute is refused: it would silently count wrong.
 */
final class DeclaredCoverage
{
    /** @var array<class-string, array<string, null|list<array{int, int}>>> */
    private static array $byTestClass = [];

    /**
     * @param class-string $testClass
     *
     * @throws LogicException                            With a Covers attribute the gate cannot count
     * @return array<string, null|list<array{int, int}>>
     */
    public static function of(string $testClass): array
    {
        return self::$byTestClass[$testClass] ??= self::resolve(testClass: $testClass);
    }

    /**
     * Whether a line is inside the declared ranges of its file.
     *
     * @param null|list<array{int, int}> $ranges null: the whole file
     */
    public static function declares(?array $ranges, int $line): bool
    {
        if ($ranges === null) {
            return true;
        }

        foreach ($ranges as [$start, $end]) {
            if ($line >= $start && $line <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $testClass
     *
     * @return array<string, null|list<array{int, int}>>
     */
    private static function resolve(string $testClass): array
    {
        $targets = [];

        foreach (new ReflectionClass(objectOrClass: $testClass)->getAttributes() as $attribute) {
            $name = $attribute->getName();

            if (!str_starts_with(haystack: $name, needle: 'PHPUnit\Framework\Attributes\Covers')) {
                continue;
            }

            $declared = $attribute->newInstance();

            match (true) {
                $declared instanceof CoversNothing => null,
                $declared instanceof CoversClass => self::addClassLike(
                    targets: $targets,
                    reflection: new ReflectionClass(objectOrClass: $declared->className())
                ),
                $declared instanceof CoversTrait => self::addClassLike(
                    targets: $targets,
                    reflection: new ReflectionClass(objectOrClass: $declared->traitName())
                ),
                $declared instanceof CoversMethod => self::addRange(
                    targets: $targets,
                    reflection: new ReflectionMethod(
                        objectOrMethod: $declared->className(),
                        method: $declared->methodName()
                    )
                ),
                $declared instanceof CoversFunction => self::addRange(
                    targets: $targets,
                    reflection: new ReflectionFunction(function: $declared->functionName())
                ),
                default => throw new LogicException(
                    message: $testClass . ': #[' . $name . '] is not supported by etel-coverage.'
                )
            };
        }

        return $targets;
    }

    /**
     * @param array<string, null|list<array{int, int}>> $targets
     * @param ReflectionClass<object>                   $reflection
     */
    private static function addClassLike(array &$targets, ReflectionClass $reflection): void
    {
        $file = $reflection->getFileName();

        if ($file !== false) {
            $targets[$file] = null;
        }

        foreach ($reflection->getTraits() as $trait) {
            self::addClassLike(targets: $targets, reflection: $trait);
        }
    }

    /**
     * @param array<string, null|list<array{int, int}>> $targets
     */
    private static function addRange(array &$targets, ReflectionFunctionAbstract $reflection): void
    {
        $file = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        if (
            $file === false
            || $start === false
            || $end === false
            || (array_key_exists(key: $file, array: $targets) && $targets[$file] === null)
        ) {
            return;
        }

        $targets[$file][] = [$start, $end];
    }
}
