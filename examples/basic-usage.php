<?php

declare(strict_types=1);

/**
 * Example: Using polyfill-ctype.
 *
 * Install:
 *   composer require symfony/polyfill-ctype
 *
 * Once installed, the ctype_* functions are available even without the ctype extension.
 * If the extension is present, the native functions are used directly (no overhead).
 */

// Check if a string contains only alphanumeric characters
$username = 'user123';
if (ctype_alnum($username)) {
    echo "Valid username: $username\n";
}

// Validate that input is all digits
$postalCode = '10001';
if (ctype_digit($postalCode)) {
    echo "Valid postal code: $postalCode\n";
}

// Check for all-alphabetic input
$name = 'Alice';
if (ctype_alpha($name)) {
    echo "Name contains only letters: $name\n";
}

// Check for uppercase
$code = 'ABC';
if (ctype_upper($code)) {
    echo "All uppercase: $code\n";
}

// Validate hex color component
$hexValue = 'FF3A2B';
if (ctype_xdigit($hexValue)) {
    echo "Valid hex string: $hexValue\n";
}
