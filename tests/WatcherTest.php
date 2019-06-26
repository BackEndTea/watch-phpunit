<?php

declare(strict_types=1);

namespace Watcher\Test;

use PHPUnit\Framework\TestCase;
use Watcher\Watcher;

final class WatcherTest extends TestCase
{
    public function testItCanBeInstantiated() : void
    {
        new Watcher([__DIR__]);
        $this->addToAssertionCount(1);
    }

    public function testItCanWatchFiles() : void
    {
        $watcher = new Watcher([__DIR__ . '/Fixtures/watchable']);
        $result  = $watcher->getAllWatchedFiles();
        $this->assertCount(3, $result);
    }
}
