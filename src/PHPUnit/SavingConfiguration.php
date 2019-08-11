<?php

declare(strict_types=1);

namespace Watcher\PHPUnit;

use DOMDocument;
use function file_put_contents;

class SavingConfiguration implements Configuration
{
    /** @var Configuration */
    private $innerConfig;
    /** @var string */
    private $filePath;

    public function __construct(Configuration $innerConfig, string $filePath)
    {
        $this->innerConfig = $innerConfig;
        $this->filePath    = $filePath;
    }

    public function removeExistingTestSuite() : void
    {
        $this->innerConfig->removeExistingTestSuite();
        file_put_contents($this->filePath, $this->innerConfig->getDom()->saveXML());
    }

    /**
     * @param array<string> $tests
     */
    public function addTestSuiteWithFilteredTestFiles(array $tests) : void
    {
        $this->innerConfig->addTestSuiteWithFilteredTestFiles($tests);
        file_put_contents($this->filePath, $this->innerConfig->getDom()->saveXML());
    }

    public function getDom() : DOMDocument
    {
        return $this->innerConfig->getDom();
    }
}
