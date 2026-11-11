<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\CsFixer;

use Etel\CodingStandard\CsFixer\NoTrailingCommaOutsideArraysFixer;
use Iterator;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

/**
 * @internal
 */
#[CoversClass(NoTrailingCommaOutsideArraysFixer::class)]
final class NoTrailingCommaOutsideArraysFixerTest extends TestCase
{
    #[Test]
    #[DataProvider('lists')]
    #[TestDox('The trailing comma goes from $_dataName')]
    public function removesTheCommaOutsideArrays(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->fix(code: $input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function lists(): Iterator
    {
        yield 'call arguments' => ["<?php\nf(\n    \$a,\n    \$b,\n);\n", "<?php\nf(\n    \$a,\n    \$b\n);\n"];

        yield 'constructor arguments' => [
            "<?php\nnew Foo(\n    \$a,\n);\n",
            "<?php\nnew Foo(\n    \$a\n);\n",
        ];

        yield 'parameters' => [
            "<?php\nfunction f(\n    int \$a,\n    int \$b,\n) {}\n",
            "<?php\nfunction f(\n    int \$a,\n    int \$b\n) {}\n",
        ];

        yield 'closure use list' => [
            "<?php\n\$f = function () use (\n    \$a,\n) {};\n",
            "<?php\n\$f = function () use (\n    \$a\n) {};\n",
        ];

        yield 'attribute arguments' => [
            "<?php\n#[Attr(\n    a: 1,\n)]\nfunction f() {}\n",
            "<?php\n#[Attr(\n    a: 1\n)]\nfunction f() {}\n",
        ];

        yield 'match arms' => [
            "<?php\n\$x = match (\$y) {\n    1 => 'a',\n    default => 'b',\n};\n",
            "<?php\n\$x = match (\$y) {\n    1 => 'a',\n    default => 'b'\n};\n",
        ];

        yield 'a comment after the last argument stays' => [
            "<?php\nf(\n    \$a, // last\n);\n",
            "<?php\nf(\n    \$a // last\n);\n",
        ];
    }

    #[Test]
    #[DataProvider('arrays')]
    #[TestDox('The trailing comma stays in $_dataName')]
    public function keepsTheCommaInArrays(string $code): void
    {
        $this->assertSame($code, $this->fix(code: $code));
    }

    /**
     * @return Iterator<string, array{string}>
     */
    public static function arrays(): Iterator
    {
        yield 'short array' => ["<?php\n\$x = [\n    1,\n    2,\n];\n"];

        yield 'long array' => ["<?php\n\$x = array(\n    1,\n    2,\n);\n"];

        yield 'destructuring' => ["<?php\n[\n    \$a,\n    \$b,\n] = \$x;\n"];

        yield 'list()' => ["<?php\nlist(\n    \$a,\n    \$b,\n) = \$x;\n"];

        yield 'an array inside a call keeps its comma, the call loses its own' => [
            "<?php\nf([\n    1,\n]);\n",
        ];

        yield 'a block that is not a match' => ["<?php\nuse A\\{\n    B,\n    C,\n};\n"];
    }

    #[Test]
    #[TestDox('The rule is named in the custom-fixer form php-cs-fixer requires')]
    public function isNamedAsACustomFixer(): void
    {
        $this->assertSame(
            'Etel/no_trailing_comma_outside_arrays',
            new NoTrailingCommaOutsideArraysFixer()->getName()
        );
    }

    private function fix(string $code): string
    {
        $tokens = Tokens::fromCode($code);
        new NoTrailingCommaOutsideArraysFixer()->fix(file: new SplFileInfo(filename: __FILE__), tokens: $tokens);

        return $tokens->generateCode();
    }
}
