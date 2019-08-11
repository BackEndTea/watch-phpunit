<?php

declare(strict_types=1);

namespace Watcher\FileSystem;

use Exception;
use function file_exists;

class PHPUnitConfigFinder
{
    public function find() : string
    {
        $options = [
            'phpunit.xml.dist',
            'phpunit.xml',
        ];
        foreach ($options as $option) {
            if (file_exists($option)) {
                return $option;
            }
        }
        throw new Exception('Unable to find phpunit configuration file');
    }
}
