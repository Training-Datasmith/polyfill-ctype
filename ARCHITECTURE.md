# Architecture: polyfill-ctype

## Purpose

Provides a pure-PHP fallback implementation of the PHP `ctype_*` extension functions for
environments where the `ctype` extension is not available. All functions are implemented
via regex and delegate to the native implementation when the extension is present.

## Directory Structure

```
Ctype.php      # Pure-PHP implementations of all ctype_* functions as static methods
bootstrap.php  # Checks for native ctype extension; defines ctype_* functions if absent
bootstrap80.php  # PHP 8.0+ variant of the bootstrap (uses match expression, no int coercion)
```

## Key Design Decisions

### Static Class as Implementation Detail

`Ctype` is marked `@internal` and `final`. Bootstrap files define the global `ctype_*`
functions that delegate to `Ctype::ctype_*()`. Users never reference the class directly;
they call the regular PHP `ctype_*()` functions, which are transparently polyfilled.

### Integer-to-Character Conversion

The ctype functions have special behavior for integers between -128 and 255: they are
treated as ASCII character codes. `Ctype::convert_int_to_char_for_ctype()` centralizes
this conversion and also fires a `E_USER_DEPRECATED` warning on PHP 8.1+ (where integer
arguments will be treated as strings in future versions).

### Two Bootstrap Files

`bootstrap80.php` is the PHP 8.0+ variant; it omits the integer argument handling (which
was deprecated in PHP 8.1+) for a cleaner implementation. The correct bootstrap is
selected by Composer's `extra.symfony.require` constraint resolution.

## Extension Points

None. This is a drop-in native function polyfill; there are no extension interfaces.
