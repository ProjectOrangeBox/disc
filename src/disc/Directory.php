<?php

declare(strict_types=1);

namespace orange\disc\disc;

use orange\disc\Disc;
use orange\disc\disc\DiscSplFileInfo;

class Directory extends DiscSplFileInfo
{
    protected const PATH_TYPE = Disc::FOLDER;

    public function name(): string
    {
        return $this->getFilename();
    }

    public function create(int $mode = 0777, bool $recursive = true): bool
    {
        $path = $this->getPath();

        $bool = true;

        if (!\file_exists($path)) {
            $umask = \umask(0);
            $bool = \mkdir($path, $mode, $recursive);
            \umask($umask);
        }

        return $bool;
    }

    public function list(string $pattern = '*', int $flags = 0, bool $recursive = false): array
    {
        $path = $this->getPath(true);

        $array = ($recursive) ? $this->listRecursive($path . DIRECTORY_SEPARATOR . $pattern, $flags) : \glob($path . DIRECTORY_SEPARATOR . $pattern, $flags);

        return Disc::stripRootPaths($array);
    }

    public function listAll(string $pattern = '*', int $flags = 0): array
    {
        return $this->list($pattern, $flags, true);
    }

    /**
     * remove old files & directories inside this directory
     */
    public function clean(int $days): void
    {
        $path = $this->getPath(true);

        // flush temp upload directory
        if (is_dir($path) && $days > 0) {
            // let's remove any uploads sitting around for X days
            $now = time();
            $dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            $dir = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($dir as $file) {
                // remove files/directories older than $days
                if ($now - filemtime((string) $file) >= 60 * 60 * 24 * $days) {
                    if ($file->isFile()) {
                        unlink((string) $file);
                    } elseif ($file->isDir()) {
                        rmdir((string) $file);
                    }
                }
            }
        }
    }

    /** protected */

    protected function listRecursive(string $pattern, int $flags = 0): array
    {
        $files = \glob($pattern, $flags);

        foreach (\glob(\dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $directory) {
            /* recursive loop */
            $files = \array_merge($files, self::listRecursive($directory . DIRECTORY_SEPARATOR . \basename($pattern), $flags));
        }

        return $files;
    }
}
