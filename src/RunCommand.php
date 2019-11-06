<?php

declare(strict_types=1);

namespace Watcher;

use Depend\DependencyFinder;
use DOMDocument;
use Exception;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Watcher\FileSystem\PHPUnitConfigFinder;
use Watcher\PHPUnit\Configuration;
use Watcher\PHPUnit\SavingConfiguration;
use Watcher\PHPUnit\XMLConfig;
use function array_filter;
use function array_map;
use function array_unique;
use function copy;
use function getcwd;
use function usleep;

final class RunCommand extends Command
{
    /** @var OutputInterface */
    private $output;

    /** @var DependencyFinder */
    private $dependencyFinder;

    /** @var Watcher */
    private $watcher;

    /** @var Configuration */
    private $configFile;

    /** @var string */
    private $configLocation;

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

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->output           = $output;
        $src                    = convertInputToPath($input->getOption('src'));
        $test                   = convertInputToPath($input->getOption('test'));
        $this->dependencyFinder = new DependencyFinder([$src, $test]);
        $this->dependencyFinder->build();
        $this->watcher = new Watcher([$src, $test]);

        $cwd = getcwd();
        if ($cwd === false) {
            throw new LogicException('Unable to determine current directory, exiting');
        }

        $this->configLocation = $cwd . '/phpunit.tmp.xml.dist';
        $originalConfig       = (new PHPUnitConfigFinder())->find();
        copy($originalConfig, $this->configLocation);
        $dom = new DOMDocument();
        $dom->load($originalConfig);
        $this->configFile = new SavingConfiguration(new XMLConfig($dom), $this->configLocation);

        $this->configFile->removeExistingTestSuite();

        $this->loop();

        return 1;
    }

    private function loop() : void
    {
        while (true) {
            if (! $this->watcher->hasChangedFiles()) {
                usleep(1000000);
                continue;
            }

            $changedFiles = array_filter(
                array_map('realpath', $this->watcher->getChangedFilesSinceLastCommit())
            );
            $this->doLoop($changedFiles);

            usleep(1000000);
        }
    }

    /**
     * @param array<string> $changedFiles
     */
    private function doLoop(array $changedFiles) : void
    {
        $this->dependencyFinder->reBuild($changedFiles);
        $changedAndRelatedFiles = array_unique(
            $this->dependencyFinder->getAllFilesDependingOn($changedFiles)
        );

        $this->configFile->addTestSuiteWithFilteredTestFiles($changedAndRelatedFiles);

        $p = new Process([
            'vendor/bin/phpunit',
            '--configuration=' . $this->configLocation,
        ]);

        $p->run();
        $this->output->write($p->getOutput());

        $this->configFile->removeExistingTestSuite();
    }
}
