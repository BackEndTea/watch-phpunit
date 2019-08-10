<?php

declare(strict_types=1);

namespace Watcher;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_values;
use function count;
use function explode;
use function realpath;
use function trim;

class Watcher
{
    /** @var array<int, string> */
    private $folders;

    /** @var array<string, int> */
    private $fileCache = [];

    /**
     * @param array<int, string> $folders
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

    public function getChangedFilesSinceLastCommit() : array
    {
        $process = Process::fromShellCommandline('git diff HEAD --name-only | paste -sd "," -');
        $process->run();

        return array_filter(
            explode(',', trim($process->getOutput())),
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
     * Retrieves all the files that have changed since the last time we checked.
     * This also updates the local cache, so each time this is reevaluated.
     * It does not return deleted items.
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
