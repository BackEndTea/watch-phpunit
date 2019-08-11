<?php

declare(strict_types=1);

namespace Watcher;

use Exception;
use function is_string;
use function realpath;
use function strlen;
use function strpos;
use function substr;

function endsWith(string $value, string $suffix) : bool
{
    return $suffix === substr($value, -strlen($suffix));
}

function startsWith(string $value, string $prefix) : bool
{
    return strpos($value, $prefix) === 0;
}

/**
 * @param mixed $input
 */
function convertInputToPath($input) : string
{
    if (! is_string($input)) {
        throw new Exception('Expected a string as input');
    }
    $path = realpath($input);
    if (! is_string($path)) {
        throw new Exception('Expected an existing file or directory as input.');
    }

    return $path;
}
