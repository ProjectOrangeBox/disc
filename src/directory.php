<?php

declare(strict_types=1);

namespace dmyers\disc;

use dmyers\disc\discSplFileInfo;

class Directory extends discSplFileInfo
{
	public function list(string $pattern = '*', int $flags = 0, bool $recursive = false): array
	{
		$path = $this->getPathname();

		Disc::directoryRequired($path);

		$array = ($recursive) ? $this->listRecursive($path . '/' . $pattern, $flags) : \glob($path . '/' . $pattern, $flags);

		return Disc::stripRootPath($array);
	}

	public function listAll(string $pattern = '*', int $flags = 0): array
	{
		return $this->list($pattern, $flags, true);
	}

	public function removeContents(bool $quiet = true): bool
	{
		return $this->remove(false, $quiet);
	}

	public function remove(bool $removeDirectory = true, bool $quiet = true): bool
	{
		$path = $this->getPathname();

		if (!$quiet) {
			Disc::directoryRequired($path);
		}

		if (is_dir($path)) {
			self::removeRecursive($path, $removeDirectory);
		}

		return true; /* ?? */
	}

	public function create(int $mode = 0777, bool $recursive = true): bool
	{
		return $this->mkdir($this->getPathname(), $mode, $recursive);
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

	protected function removeRecursive(string $path, bool $removeDirectory = true)
	{
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $fileinfo) {
			if ($fileinfo->isDir()) {
				self::removeRecursive($fileinfo->getPathname());
			} else {
				\unlink($fileinfo->getPathname());
			}
		}

		if ($removeDirectory) {
			\rmdir($path);
		}
	}

	protected function mkdir(string $path, int $mode = 0777, bool $recursive = true): bool
	{
		if (!\file_exists($path)) {
			$umask = \umask(0);
			$bool = \mkdir($path, $mode, $recursive);
			\umask($umask);
		} else {
			$bool = true;
		}

		return $bool;
	}
} /* end class */
