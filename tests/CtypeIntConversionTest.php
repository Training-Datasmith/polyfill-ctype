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

class CtypeIntConversionTest extends CtypeTestCase
{
    /**
     * @dataProvider integerClassificationProvider
     */
    public function testIntegerCodePointsAndDecimalStrings(int $input, array $expectedByMethod): void
    {
        $methods = self::ctypeMethods();
        $this->assertCount(\count($methods), $expectedByMethod);

        $deprecationType = \PHP_VERSION_ID >= 80100 ? 'int' : null;

        foreach ($methods as $index => $method) {
            $this->assertCtype($method, $input, $expectedByMethod[$index], $deprecationType);
        }
    }

    public function integerClassificationProvider(): array
    {
        $cases = [];
        foreach (require __DIR__.'/fixtures/integer_classification.php' as $input => $expected) {
            $cases[] = [$input, $expected];
        }

        return $cases;
    }

    public function testInRangeIntegerDeprecationMentionsTheCalledFunction(): void
    {
        if (\PHP_VERSION_ID >= 80100) {
            $this->assertCtype('ctype_digit', 48, true, 'int');
            $this->assertCtype('ctype_upper', 65, true, 'int');
        } else {
            $this->assertCtype('ctype_digit', 48, true);
            $this->assertCtype('ctype_upper', 65, true);
        }
    }

    public function testOutOfRangeIntegerIsDeprecatedOnPhp81(): void
    {
        $deprecationType = \PHP_VERSION_ID >= 80100 ? 'int' : null;

        $this->assertCtype('ctype_digit', 256, true, $deprecationType);
        $this->assertCtype('ctype_digit', -129, false, $deprecationType);
        $this->assertCtype('ctype_digit', \PHP_INT_MAX, true, $deprecationType);
    }

    public function testNonStringDeprecationOnPhp81(): void
    {
        $cases = [
            [null, 'null', false],
            [false, 'bool', false],
            [true, 'bool', null],
            [1.5, 'float', null],
            [[], 'array', null],
            [new \stdClass(), 'stdClass', null],
        ];

        foreach ($cases as [$input, $type, $expectedBool]) {
            if (null !== $expectedBool) {
                $deprecationType = \PHP_VERSION_ID >= 80100 ? $type : null;
                $this->assertCtype('ctype_digit', $input, $expectedBool, $deprecationType);
            } else {
                if (\PHP_VERSION_ID >= 80100) {
                    [$result, $deprecations] = $this->invokeCtype('ctype_digit', $input);
                    $this->assertCount(1, $deprecations);
                    $this->assertSame(
                        'ctype_digit(): Argument of type '.$type.' will be interpreted as string in the future',
                        $deprecations[0]['message']
                    );
                } else {
                    [$result, $deprecations] = $this->invokeCtype('ctype_digit', $input);
                    $this->assertSame([], $deprecations);
                }
            }
        }
    }
}
