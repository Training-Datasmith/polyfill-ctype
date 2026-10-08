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

class BootstrapTest extends TestCase
{
    private const CTYPE_FUNCTIONS = [
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

    public function testPolyfillDefinesFunctionsWhenExtensionIsMissing(): void
    {
        if (\extension_loaded('ctype')) {
            $this->markTestSkipped(
                'The ctype extension is loaded in this PHP build; run scripts/run-alpine-no-ctype-gate.sh for the missing-extension bootstrap branch.'
            );
        }

        foreach (self::CTYPE_FUNCTIONS as $function) {
            $this->assertTrue(\function_exists($function), $function.' should be defined by the polyfill bootstrap');
        }

        $ref = new \ReflectionFunction('ctype_digit');
        $this->assertFalse($ref->isInternal());

        if (\PHP_VERSION_ID >= 80000) {
            $this->assertSame('bootstrap80.php', \basename($ref->getFileName()));
            $param = $ref->getParameters()[0];
            $return = $ref->getReturnType();
            $this->assertTrue($param->hasType());
            $this->assertSame('mixed', (string) $param->getType());
            $this->assertNotNull($return);
            $this->assertSame('bool', (string) $return);
        } else {
            $this->assertSame('bootstrap.php', \basename($ref->getFileName()));
            $param = $ref->getParameters()[0];
            $this->assertFalse($param->hasType());
            $this->assertNull($ref->getReturnType());
        }

        $this->assertTrue(\ctype_digit('123'));
        $this->assertFalse(\ctype_digit('12a'));
        $this->assertFalse(\ctype_digit(''));
        $this->assertTrue(\ctype_alnum('Abc'));
        $this->assertFalse(\ctype_alnum('Abc!'));
        $this->assertTrue(\ctype_alpha(65));
        $this->assertTrue(\ctype_digit(256));
    }

    public function testBootstrapSurvivesASecondInclude(): void
    {
        require __DIR__.'/../bootstrap.php';
        require __DIR__.'/../bootstrap.php';

        $this->assertTrue(\ctype_digit('5'));
    }

    public function testBootstrapLeavesExtensionFunctionsInPlace(): void
    {
        if (!\extension_loaded('ctype')) {
            $this->markTestSkipped(
                'The ctype extension is not loaded; this branch is exercised on official php:*-cli images with a built-in ctype extension.'
            );
        }

        require __DIR__.'/../bootstrap.php';

        $ref = new \ReflectionFunction('ctype_digit');
        $this->assertTrue($ref->isInternal());
        $this->assertTrue(\ctype_digit('123'));
        $this->assertFalse(\ctype_digit('12a'));
    }
}
