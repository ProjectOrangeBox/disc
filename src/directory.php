<?php

declare(strict_types=1);

namespace dmyers\disc;

use dmyers\disc\discSplFileInfo;
use dmyers\disc\exceptions\DirectoryException;

class Directory extends DiscSplFileInfo
{
	const TYPE = 'directory';

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

		$array = ($recursive) ? $this->listRecursive($path . '/' . $pattern, $flags) : \glob($path . '/' . $pattern, $flags);

		return Disc::stripRootPaths($array);
	}

	public function listAll(string $pattern = '*', int $flags = 0): array
	{
		return $this->list($pattern, $flags, true);
	}

	public function copy(string $destination): self
	{
		$destination = Disc::resolve($destination);

		if (file_exists($destination)) {
			throw new DirectoryException('Destination already exsists');
		}

		$this->copyRecursive($this->getPath(true), $destination);

		/* return reference to new directory */
		return new Directory($destination);
	}

	public function remove(bool $removeDirectory = true, bool $quiet = false): bool
	{
		$path = $this->getPath(!$quiet);

		if (is_dir($path)) {
			self::removeRecursive($path, $removeDirectory);
		}

		return true; /* ?? */
	}

	public function removeContents(bool $quiet = false): bool
	{
		return $this->remove(false, $quiet);
	}

	/* move & rename in DiscSplFileInfo */

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

	protected function copyRecursive(string $source, string $destination): void
	{
		$dir = \opendir($source);

		if (!is_dir($destination)) {
			(new Directory($destination))->create();
		}

		while ($file = \readdir($dir)) {
			if (($file != '.') && ($file != '..')) {
				if (\is_dir($source . '/' . $file)) {
					$this->copyRecursive($source . '/' . $file, $destination . '/' . $file);
				} else {
					\copy($source . '/' . $file, $destination . '/' . $file);
				}
			}
		}

		\closedir($dir);
	}
} /* end class */
