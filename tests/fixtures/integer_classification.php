<?php

/*
 * Expected booleans for integer inputs. Order matches CtypeTestCase::ctypeMethods().
 */

return [
    -129 => [false, false, false, false, true, false, true, false, false, false, false],
    -128 => [false, false, false, false, false, false, false, false, false, false, false],
    -1 => [false, false, false, false, false, false, false, false, false, false, false],
    0 => [false, false, true, false, false, false, false, false, false, false, false],
    9 => [false, false, true, false, false, false, false, false, true, false, false],
    10 => [false, false, true, false, false, false, false, false, true, false, false],
    13 => [false, false, true, false, false, false, false, false, true, false, false],
    31 => [false, false, true, false, false, false, false, false, false, false, false],
    32 => [false, false, false, false, false, false, true, false, true, false, false],
    33 => [false, false, false, false, true, false, true, true, false, false, false],
    47 => [false, false, false, false, true, false, true, true, false, false, false],
    58 => [false, false, false, false, true, false, true, true, false, false, false],
    64 => [false, false, false, false, true, false, true, true, false, false, false],
    91 => [false, false, false, false, true, false, true, true, false, false, false],
    96 => [false, false, false, false, true, false, true, true, false, false, false],
    123 => [false, false, false, false, true, false, true, true, false, false, false],
    126 => [false, false, false, false, true, false, true, true, false, false, false],
    48 => [true, false, false, true, true, false, true, false, false, false, true],
    57 => [true, false, false, true, true, false, true, false, false, false, true],
    65 => [true, true, false, false, true, false, true, false, false, true, true],
    70 => [true, true, false, false, true, false, true, false, false, true, true],
    90 => [true, true, false, false, true, false, true, false, false, true, false],
    71 => [true, true, false, false, true, false, true, false, false, true, false],
    97 => [true, true, false, false, true, true, true, false, false, false, true],
    102 => [true, true, false, false, true, true, true, false, false, false, true],
    122 => [true, true, false, false, true, true, true, false, false, false, false],
    103 => [true, true, false, false, true, true, true, false, false, false, false],
    127 => [false, false, true, false, false, false, false, false, false, false, false],
    128 => [false, false, false, false, false, false, false, false, false, false, false],
    255 => [false, false, false, false, false, false, false, false, false, false, false],
    256 => [true, false, false, true, true, false, true, false, false, false, true],
    -256 => [false, false, false, false, true, false, true, false, false, false, false],
    PHP_INT_MAX => [true, false, false, true, true, false, true, false, false, false, true],
    PHP_INT_MIN => [false, false, false, false, true, false, true, false, false, false, false],
];
