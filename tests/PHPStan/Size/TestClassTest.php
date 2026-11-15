<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan\Size;

use Etel\CodingStandard\PHPStan\Size\TestClass;
use Etel\CodingStandardTests\Fixtures\PHPStan\Size\Measured;
use Etel\CodingStandardTests\Fixtures\PHPStan\Size\MeasuredTest;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TestClass::class)]
final class TestClassTest extends PHPStanTestCase
{
    #[Test]
    #[TestDox('TestCase and its subclasses are test code; other classes, outside the test paths, are not')]
    public function tellsTestClasses(): void
    {
        $reflection = self::createReflectionProvider();
        $tests = new TestClass(paths: []);

        $this->assertTrue($tests->is(class: $reflection->getClass(className: MeasuredTest::class), file: 'a.php'));
        $this->assertTrue($tests->is(class: $reflection->getClass(className: TestCase::class), file: 'a.php'));
        $this->assertFalse($tests->is(class: $reflection->getClass(className: Measured::class), file: 'a.php'));
    }

    #[Test]
    #[TestDox('Any class in a file under one of the test paths is test code, whatever the directory separator')]
    public function tellsTestPaths(): void
    {
        $measured = self::createReflectionProvider()->getClass(className: Measured::class);
        $tests = new TestClass(paths: ['/tests/']);

        $this->assertTrue($tests->is(class: $measured, file: '/app/tests/Support/Helper.php'));
        $this->assertTrue($tests->is(class: $measured, file: 'C:\app\tests\Support\Helper.php'));
        $this->assertFalse($tests->is(class: $measured, file: '/app/src/Helper.php'));
    }
}
