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

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Ctype\Ctype;

abstract class CtypeTestCase extends TestCase
{
    /**
     * @return string[]
     */
    protected static function ctypeMethods(): array
    {
        return [
            'ctype_alnum',
            'ctype_alpha',
            'ctype_cntrl',
            'ctype_digit',
            'ctype_graph',
            'ctype_lower',
            'ctype_print',
            'ctype_punct',
            'ctype_space',
            'ctype_upper',
            'ctype_xdigit',
        ];
    }

    /**
     * @param mixed $input
     *
     * @return array{0: bool, 1: list<array{severity: int, message: string}>}
     */
    protected function invokeCtype(string $method, $input): array
    {
        $deprecations = [];
        set_error_handler(
            function (int $severity, string $message, string $file, int $line) use (&$deprecations): bool {
                $deprecations[] = ['severity' => $severity, 'message' => $message];

                return true;
            },
            \E_USER_DEPRECATED
        );

        try {
            $result = Ctype::$method($input);
        } finally {
            restore_error_handler();
        }

        return [$result, $deprecations];
    }

    protected function assertCtype(string $method, $input, bool $expected, ?string $expectedDeprecationType = null): void
    {
        [$result, $deprecations] = $this->invokeCtype($method, $input);

        $this->assertSame($expected, $result);

        if (\PHP_VERSION_ID < 80100) {
            $this->assertSame([], $deprecations, 'No deprecations expected on PHP < 8.1');

            return;
        }

        if (\is_string($input)) {
            $this->assertSame([], $deprecations, 'String inputs must not trigger deprecations');

            return;
        }

        if (null === $expectedDeprecationType) {
            $this->assertSame([], $deprecations, 'Unexpected deprecation');

            return;
        }

        $this->assertCount(1, $deprecations);
        $this->assertSame(\E_USER_DEPRECATED, $deprecations[0]['severity']);
        $this->assertSame(
            $method.'(): Argument of type '.$expectedDeprecationType.' will be interpreted as string in the future',
            $deprecations[0]['message']
        );
    }
}
