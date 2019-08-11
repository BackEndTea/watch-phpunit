<?php

declare(strict_types=1);

namespace Watcher\PHPUnit;

use DOMDocument;
use DOMNode;
use DOMXPath;
use function assert;

class XMLConfig implements Configuration
{
    /** @var DOMDocument */
    private $dom;
    /** @var DOMXPath */
    private $xPath;

    public function __construct(DOMDocument $dom)
    {
        $this->dom   = $dom;
        $this->xPath = new DOMXPath($this->dom);
    }

    public function removeExistingTestSuite() : void
    {
        $nodes = $this->xPath->query('/phpunit/testsuites/testsuite');

        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        // handle situation when test suite is directly inside root node
        $nodes = $this->xPath->query('/phpunit/testsuite');

        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * @param array<string> $tests
     */
    public function addTestSuiteWithFilteredTestFiles(array $tests) : void
    {
        $testSuites            = $this->xPath->query('/phpunit/testsuites');
        $nodeToAppendTestSuite = $testSuites->item(0);

        // if there is no `testsuites` node, append to root
        if (! $nodeToAppendTestSuite) {
            $nodeToAppendTestSuite = $testSuites = $this->xPath->query('/phpunit')->item(0);
        }

        $testSuite = $this->dom->createElement('testsuite');
        $testSuite->setAttribute('name', 'Filtered Test Suite');

        foreach ($tests as $testFilePath) {
            $file = $this->dom->createElement('file', $testFilePath);

            $testSuite->appendChild($file);
        }

        assert($nodeToAppendTestSuite instanceof DOMNode);

        $nodeToAppendTestSuite->appendChild($testSuite);
    }

    public function getDom() : DOMDocument
    {
        return $this->dom;
    }
}
