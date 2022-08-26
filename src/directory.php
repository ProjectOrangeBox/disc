<?php

declare(strict_types=1);

namespace dmyers\orange\disc;

use SplFileInfo;
use dmyers\orange\disc\exceptions\DirectoryException;

class Directory extends SplFileInfo
{
	public function list(string $pattern = '*', int $flags = 0): array
	{
		$path = $this->getPathname();

		Disc::directoryRequired($path);

		return Disc::stripRootPath(\glob($path . '/' . $pattern, $flags));
	}

	public function listAll(string $pattern = '*', int $flags = 0): array
	{
		$path = $this->getPathname();

		Disc::directoryRequired($path);

		return Disc::stripRootPath(self::listRecursive($path . '/' . $pattern, $flags));
	}

	protected function listRecursive(string $pattern, int $flags = 0): array
	{
		$files = \glob($pattern, $flags);

		foreach (\glob(\dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $directory) {
			/* recursive loop */
			$files = \array_merge($files, self::listRecursive($directory . DIRECTORY_SEPARATOR . \basename($pattern), $flags));
		}

		return $files;
	}

	public function removeContents(): bool
	{
		return $this->remove(false);
	}

	public function remove(bool $removeDirectory = true): bool
	{
		$path = $this->getPathname();

		Disc::directoryRequired($path);

		self::removeRecursive($path, $removeDirectory);

		return true; /* ?? */
	}

	protected function removeRecursive(string $path, bool $removeDirectory = true)
	{
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $fileinfo) {
			if ($fileinfo->isDir()) {
				self::removeRecursive($fileinfo->getRealPath());
			} else {
				\unlink($fileinfo->getRealPath());
			}
		}

		if ($removeDirectory) {
			\rmdir($path);
		}
	}

	public function copy(string $destination): bool
	{
		$source = $this->getPathname();

		Disc::directoryRequired($source);

		$destination = Disc::resolve($destination);

		$this->copyRecursive($source, $destination);

		return true;
	}

	protected function copyRecursive(string $source, string $destination): void
	{
		$dir = opendir($source);

		if (!is_dir($destination)) {
			$this->mkdir($destination, 0777, true);
		}

		while (($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($source . '/' . $file)) {
					$this->copyRecursive($source . '/' . $file, $destination . '/' . $file);
				} else {
					copy($source . '/' . $file, $destination . '/' . $file);
				}
			}
		}

		closedir($dir);
	}

	public function rename(string $name)
	{
		return Disc::renameDirectory($this, $name);
	}

	public function create(int $mode = 0777, bool $recursive = true): bool
	{
		return $this->mkdir($this->getPathname(), $mode, $recursive);
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
