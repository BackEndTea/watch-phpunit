<?php

declare(strict_types=1);

namespace Watcher;

use Depend\DependencyFinder;
use DOMDocument;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Watcher\FileSystem\PHPUnitConfigFinder;
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
        $src              = convertInputToPath($input->getOption('src'));
        $test             = convertInputToPath($input->getOption('test'));
        $finder           = new PHPUnitConfigFinder();
        $dependencyFinder = new DependencyFinder([$src, $test]);
        $dependencyFinder->build();
        $watcher = new Watcher([$src, $test]);

        $outFile        = getcwd() . '/phpunit.tmp.xml.dist';
        $originalConfig = $finder->find();
        copy($originalConfig, $outFile);
        $dom = new DOMDocument();
        $dom->load($originalConfig);
        $configFile = new SavingConfiguration(new XMLConfig($dom), $outFile);

        $configFile->removeExistingTestSuite();

        while (true) {
            if (! $watcher->hasChangedFiles()) {
                usleep(1000000);
                continue;
            }

            $changedFiles = array_filter(array_map('realpath', $watcher->getChangedFilesSinceLastCommit()));
            $dependencyFinder->reBuild($changedFiles);
            $changedAndRelatedFiles = array_unique($dependencyFinder->getAllFilesDependingOn($changedFiles));

            $configFile->addTestSuiteWithFilteredTestFiles($changedAndRelatedFiles);

            $p = new Process([
                'vendor/bin/phpunit',
                '--configuration=' . $outFile,
            ]);

            $p->run();
            $output->write($p->getOutput());

            $configFile->removeExistingTestSuite();

            usleep(1000000);
        }

        return 1;
    }
}
