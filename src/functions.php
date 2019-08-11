<?php

declare(strict_types=1);

namespace Watcher;

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
