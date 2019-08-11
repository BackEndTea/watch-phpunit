<?php

declare(strict_types=1);

namespace Watcher\PHPUnit;

use DOMDocument;

interface Configuration
{
    public function removeExistingTestSuite() : void;

    /**
     * @param array<string> $tests
     */
    public function addTestSuiteWithFilteredTestFiles(array $tests) : void;

    public function getDom() : DOMDocument;
}
