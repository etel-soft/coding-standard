<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\PHPStan\Size;

use Etel\CodingStandard\PHPStan\Size\CognitiveComplexity;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

/**
 * @internal
 */
#[CoversClass(CognitiveComplexity::class)]
final class CognitiveComplexityTest extends TestCase
{
    #[Test]
    #[TestDox('Branches, nesting, closures, labelled jumps and boolean runs add up as documented on the fixture')]
    public function countsMethods(): void
    {
        $class = self::measured();

        $this->assertSame(0, CognitiveComplexity::of(node: $class->getMethod(name: 'flat') ?? $class));
        $this->assertSame(12, CognitiveComplexity::of(node: $class->getMethod(name: 'nested') ?? $class));
        $this->assertSame(6, CognitiveComplexity::of(node: $class->getMethod(name: 'jumps') ?? $class));
    }

    #[Test]
    #[TestDox('A class’s complexity is the sum of its methods’')]
    public function sumsClass(): void
    {
        $this->assertSame(18, CognitiveComplexity::ofClass(class: self::measured()));
    }

    private static function measured(): Class_
    {
        $code = (string) file_get_contents(filename: __DIR__ . '/../../Fixtures/PHPStan/Size/Measured.php');
        $class = new NodeFinder()->findFirstInstanceOf(
            nodes: new ParserFactory()->createForNewestSupportedVersion()->parse(code: $code) ?? [],
            class: Class_::class
        );
        self::assertInstanceOf(Class_::class, $class);

        return $class;
    }
}
