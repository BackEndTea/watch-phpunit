<?php

declare(strict_types=1);

namespace Watcher;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function array_flip;
use function array_key_exists;
use function array_values;

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
        return array_values(array_flip($this->fileCache));
    }

    /**
     * Retrieves all the files that have changed since the last time we checked.
     * This also updates the local cache, so each time this is reevaluated.
     * It does not return deleted items.
     *
     * @return array<int,string> list of changed files
     */
    public function getChangedFiles() : array
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

        return $changedFileList;
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
