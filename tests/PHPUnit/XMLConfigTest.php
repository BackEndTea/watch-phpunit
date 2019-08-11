<?php

declare(strict_types=1);

namespace Watcher\Test\PHPUnit;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Watcher\PHPUnit\XMLConfig;

class XMLConfigTest extends TestCase
{
    public function testItRemovesTheOriginalTestSuites() : void
    {
        $config = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="./vendor/autoload.php"
         colors="true"
         executionOrder="random"
         verbose="true"
         cacheResultFile="./.build/.phpunit.cache"
>
    <testsuites>
        <testsuite name="Application Test Suite">
            <directory>./tests/</directory>
            <exclude>./tests/Fixtures</exclude>
        </testsuite>
    </testsuites>

    <filter>
        <whitelist>
            <directory>./src/</directory>
        </whitelist>
    </filter>
</phpunit>
XML;
        $dom    = new DOMDocument();
        $dom->loadXML($config);
        $xmlConfig = new XMLConfig($dom);
        $xmlConfig->removeExistingTestSuite();
        $dom = $xmlConfig->getDom();
        $this->assertSame(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd" bootstrap="./vendor/autoload.php" colors="true" executionOrder="random" verbose="true" cacheResultFile="./.build/.phpunit.cache">
    <testsuites>
        
    </testsuites>

    <filter>
        <whitelist>
            <directory>./src/</directory>
        </whitelist>
    </filter>
</phpunit>

XML
            , $dom->saveXML());
    }

    public function testItCanBuildANewTestSuite() : void
    {
        $config = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd" bootstrap="./vendor/autoload.php" colors="true" executionOrder="random" verbose="true" cacheResultFile="./.build/.phpunit.cache">
    <testsuites>
        
    </testsuites>

    <filter>
        <whitelist>
            <directory>./src/</directory>
        </whitelist>
    </filter>
</phpunit>

XML;
        $dom    = new DOMDocument();
        $dom->loadXML($config);
        $xmlConfig = new XMLConfig($dom);
        $xmlConfig->addTestSuiteWithFilteredTestFiles(['foo/test/x.php', 'a/b/c.php']);
        $dom = $xmlConfig->getDom();
        $this->assertSame(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd" bootstrap="./vendor/autoload.php" colors="true" executionOrder="random" verbose="true" cacheResultFile="./.build/.phpunit.cache">
    <testsuites>
        
    <testsuite name="Filtered Test Suite"><file>foo/test/x.php</file><file>a/b/c.php</file></testsuite></testsuites>

    <filter>
        <whitelist>
            <directory>./src/</directory>
        </whitelist>
    </filter>
</phpunit>

XML
        , $dom->saveXML());
    }
}
