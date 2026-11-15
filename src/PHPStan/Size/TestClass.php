<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan\Size;

use PHPStan\Reflection\ClassReflection;

use function in_array;
use function str_contains;
use function str_replace;

/**
 * Tells test code from the code under test: the size rules hold it to other limits. Test code is a PHPUnit test
 * case, or anything in a file whose path holds one of the fragments (doubles, fixtures, helpers under `/tests/`).
 */
final readonly class TestClass
{
    private const string TEST_CASE = 'PHPUnit\Framework\TestCase';

    /**
     * @param list<string> $paths Path fragments of test code, `/` as the separator
     */
    public function __construct(private array $paths) {}

    public function is(ClassReflection $class, string $file): bool
    {
        $path = str_replace(search: '\\', replace: '/', subject: $file);

        foreach ($this->paths as $fragment) {
            if (str_contains(haystack: $path, needle: $fragment)) {
                return true;
            }
        }

        return $class->getName() === self::TEST_CASE
            || in_array(needle: self::TEST_CASE, haystack: $class->getParentClassesNames(), strict: true);
    }
}
