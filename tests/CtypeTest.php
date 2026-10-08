<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Ctype\Tests;

class CtypeTest extends CtypeTestCase
{
    public function testEmptyStringIsFalseForEveryFunction(): void
    {
        foreach (self::ctypeMethods() as $method) {
            $this->assertCtype($method, '', false);
        }
    }

    /**
     * @dataProvider asciiClassificationProvider
     */
    public function testAsciiClassification(string $input, array $expectedByMethod): void
    {
        $methods = self::ctypeMethods();
        $this->assertCount(\count($methods), $expectedByMethod);

        foreach ($methods as $index => $method) {
            $this->assertCtype($method, $input, $expectedByMethod[$index]);
        }
    }

    public function asciiClassificationProvider(): array
    {
        $cases = [];
        foreach (require __DIR__.'/fixtures/ascii_classification.php' as $row) {
            $cases[] = [$row[0], $row[1]];
        }

        return $cases;
    }

    public function testOneBadByteRejectsTheWholeString(): void
    {
        $this->assertCtype('ctype_alpha', 'abc!', false);
        $this->assertCtype('ctype_digit', '123a', false);
        $this->assertCtype('ctype_space', " \tX", false);
    }
}
