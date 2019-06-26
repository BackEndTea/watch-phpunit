<?php

declare(strict_types=1);

namespace Watcher;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function array_map;
use function assert;
use function copy;
use function file_put_contents;
use function getcwd;
use function is_string;
use function realpath;
use function strlen;
use function strpos;
use function substr;
use function usleep;

final class RunCommand extends Command
{
    protected function configure() : void
    {
        $this->setName('watch')
            ->setDescription('Start watching the files')
            ->addOption(
                'src',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source folder to watch',
                './src'
            )
            ->addOption(
                'test',
                't',
                InputOption::VALUE_OPTIONAL,
                'Test folder to watch',
                './tests'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $src = $input->getOption('src');
        assert(is_string($src));
        $test = $input->getOption('test');
        assert(is_string($test));
        $watcher = new Watcher([
            $src,
            $test,
        ]);
        copy('./phpunit.xml.dist', getcwd() . '/phpunit.tmp.xml.dist');
        $dom = new DOMDocument();
        $dom->load('./phpunit.tmp.xml.dist');
        $xPath = new DOMXPath($dom);
        $this->removeExistingTestSuite($xPath);
        file_put_contents('./phpunit.tmp.xml.dist', $dom->saveXML());

        while (true) {
            $changedFiles = $watcher->getChangedFiles();
            foreach ($changedFiles as $change) {
                $output->writeln('File Changed: ' . $change);
            }
            if ($changedFiles !== []) {
                $changedFiles = array_map(function ($filePath) use ($src, $test) : string {
                    if ($this->stringStartsWith($filePath, $src)) {
                        return $this->replaceLastCharactersWith(
                            $this->replaceFirstCharactersWith($filePath, $src, $test),
                            '.php',
                            'Test.php'
                        );
                    }

                    return $filePath;
                }, $changedFiles);
                foreach ($changedFiles as $change) {
                    $output->writeln('Running Test: ' . $change);
                }
                $this->addTestSuiteWithFilteredTestFiles($changedFiles, $dom, $xPath);
                file_put_contents('./phpunit.tmp.xml.dist', $dom->saveXML());
                $p = new Process([
                    'vendor/bin/phpunit',
                    '--configuration=' . realpath('./phpunit.tmp.xml.dist'),
                ]);
                $p->start(static function (string $type, string $buffer) use ($output) : void {
                    $output->write($buffer);
                });
                while ($p->isRunning()) {
                    usleep(100000);
                }

                $output->writeln($p->getOutput());
                $this->removeExistingTestSuite($xPath);
                file_put_contents('./phpunit.tmp.xml.dist', $dom->saveXML());
            }
            usleep(1000000);
        }

        return 1;
    }

    private function removeExistingTestSuite(DOMXPath $xPath) : void
    {
        $nodes = $xPath->query('/phpunit/testsuites/testsuite');

        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        // handle situation when test suite is directly inside root node
        $nodes = $xPath->query('/phpunit/testsuite');

        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * @param array<int, string> $tests
     */
    private function addTestSuiteWithFilteredTestFiles(array $tests, DOMDocument $dom, DOMXPath $xPath) : void
    {
        $testSuites            = $xPath->query('/phpunit/testsuites');
        $nodeToAppendTestSuite = $testSuites->item(0);

        // if there is no `testsuites` node, append to root
        if (! $nodeToAppendTestSuite) {
            $nodeToAppendTestSuite = $testSuites = $xPath->query('/phpunit')->item(0);
        }

        $testSuite = $dom->createElement('testsuite');
        $testSuite->setAttribute('name', 'Filtered Test Suite');

        foreach ($tests as $testFilePath) {
            $file = $dom->createElement('file', $testFilePath);

            $testSuite->appendChild($file);
        }

        assert($nodeToAppendTestSuite instanceof DOMNode);

        $nodeToAppendTestSuite->appendChild($testSuite);
    }

    private function stringStartsWith(string $string, string $starts) : bool
    {
        return strpos($string, $starts) === 0;
    }

    private function replaceFirstCharactersWith(string $string, string $remove, string $prepend) : string
    {
        return $prepend . substr($string, strlen($remove));
    }

    private function replaceLastCharactersWith(string $string, string $remove, string $append) : string
    {
        return substr($string, 0, - strlen($remove)) . $append;
    }
}
