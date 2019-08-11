<?php

declare(strict_types=1);

namespace Watcher;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function count;
use function explode;
use function realpath;
use function trim;

class Watcher
{
    /** @var array<string> */
    private $folders;

    /** @var array<string, int> */
    private $fileCache = [];

    /**
     * @param array<string> $folders
     */
    public function __construct(array $folders)
    {
        $this->folders = $folders;
        foreach ($this->createFileFinder() as $file) {
            $this->fileCache[$file->getPathname()] =  $file->getMTime();
        }
    }

    /**
     * @return array<int, string>
     */
    public function getAllWatchedFiles() : array
    {
        return array_keys($this->fileCache);
    }

    /**
     * @return array<string>
     */
    public function getChangedFilesSinceLastCommit() : array
    {
        /** @var Process $process */
        $process = new Process([
            'git',
            'diff',
            'HEAD',
            '--name-only',
        ]);

        $process->run();

        return array_filter(
            explode("\n", trim($process->getOutput())),
            function (string $fileName) {
                $full = realpath($fileName);

                if ($full === false) {
                    return false;
                }
                if (! endsWith($fileName, '.php')) {
                    return false;
                }

                foreach ($this->folders as $folder) {
                    if (startsWith($full, $folder)) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Determines if any files have changed since the last time this function was called.
     */
    public function hasChangedFiles() : bool
    {
        $oldFileList = $this->fileCache;

        /** @var array<int, string> $changedFileList */
        $changedFileList = [];

        /** @var array<string, int> $newFullFileList*/
        $newFullFileList = [];

        foreach ($this->createFileFinder() as $file) {
            $newFullFileList[$file->getPathname()] = $file->getMTime();
            if (array_key_exists($file->getPathname(), $oldFileList) &&
                $oldFileList[$file->getPathname()] === $file->getMTime()
            ) {
                continue;
            }

            $changedFileList[] = $file->getPathname();
        }
        $this->fileCache = $newFullFileList;

        return count($changedFileList) !== 0;
    }

    /**
     * @return iterable<SplFileInfo>|SplFileInfo[]
     */
    private function createFileFinder() : iterable
    {
        return Finder::create()
            ->in($this->folders)
            ->files()
            ->name('*.php')
            ->getIterator();
    }
}
