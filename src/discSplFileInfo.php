<?php

declare(strict_types=1);

namespace dmyers\disc;

use SplFileInfo;
use dmyers\disc\exceptions\DiscException;

class discSplFileInfo extends SplFileInfo
{
	/**
	 * Method touch
	 *
	 * @return bool
	 */
	public function touch(): bool
	{
		return \touch($this->getPathname());
	}

	/**
	 * info — Gives information about a file
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return mixed
	 */
	public function info(?string $option = null, $arg1 = null) /* array|false */
	{
		$info = [];

		$info += \stat($this->getPathname());
		$info += \pathInfo($this->getPathname());

		$info['dirname'] = disc::resolve($info['dirname'], true);

		$info['type'] = $this->getType();

		$dateFormat = ($arg1) ? $arg1 : 'r';

		$info['atime_display'] = $this->accessTime($dateFormat);
		$info['mtime_display'] = $this->modificationTime($dateFormat);
		$info['ctime_display'] = $this->changeTime($dateFormat);

		$permissions = $this->getPerms();

		$info['permissions_display'] = disc::formatPermissions($permissions, 3);
		$info['permissions_t'] = disc::formatPermissions($permissions, 1);
		$info['permissions_ugw'] = disc::formatPermissions($permissions, 2);


		$info['uid_display'] = $this->owner(true)['name'];
		$info['gid_display'] = $this->group(true)['name'];

		$info['size_display'] = disc::formatSize($this->size());

		$info['isDirectory'] = (bool)$this->isDirectory();
		$info['isWritable'] = (bool)$this->isWritable();
		$info['isReadable'] = (bool)$this->isReadable();
		$info['isFile'] = (bool)$this->isFile();

		$info['root'] = disc::getRoot();

		if ($option) {
			if (!in_array($option, $info)) {
				throw new DiscException('Unknown option ' . $option);
			}

			$info = $info[$option];
		}

		return $info;
	}

	/**
	 * Method isDirectory
	 *
	 * @return bool
	 */
	public function isDirectory(): bool
	{
		return $this->isDir();
	}

	/**
	 * dirname — Returns a parent directory's path
	 *
	 * @param string $path [path to file/directory]
	 * @param int $levels The number of parent directories to go up.
	 *
	 * @return string
	 */
	public function directory(): string
	{
		return disc::resolve($this->getPath(), true);
	}

	/**
	 * filesize — Gets file size
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return mixed
	 */
	public function size(): int
	{
		return $this->getSize();
	}

	/**
	 * fileatime — Gets last access time of file
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return int
	 */
	public function accessTime(string $dateFormat = null) /* int|string */
	{
		return disc::formatTime($this->getATime(), $dateFormat);
	}

	/**
	 * filectime — Gets inode change time of file
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return int
	 */
	public function changeTime(string $dateFormat = null) /* int|string */
	{
		return disc::formatTime($this->getCTime(), $dateFormat);
	}

	/**
	 * filemtime — Gets file modification time
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return int
	 */
	public function modificationTime(string $dateFormat = null) /* int|string */
	{
		return disc::formatTime($this->getMTime(), $dateFormat);
	}

	/**
	 * filegroup — Gets file group
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return mixed
	 */
	public function group(bool $details = false) /* array|int|false */
	{
		$id = $this->getGroup();

		return ($id && $details) ? posix_getgrgid($id) : $id;
	}

	/**
	 * fileowner — Gets file owner
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return int
	 */
	public function owner(bool $details = false) /* array|int|false */
	{
		$id = $this->getOwner();

		return ($id && $details) ? posix_getpwuid($id) : $id;
	}

	/**
	 * fileperms — Gets file permissions
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return int
	 */
	public function permissions(int $options = 0)
	{
		return ($options) ? disc::formatPermissions($this->getPerms(), $options) : $this->getPerms();
	}

	/**
	 * Method changePermissions
	 *
	 * @param string $requiredPath [explicite description]
	 * @param int $mode [explicite description]
	 *
	 * @return bool
	 */
	public function changePermissions(int $mode): bool
	{
		return \chmod($this->getPathname(), $mode);
	}

	/**
	 * Method changeGroup
	 *
	 * @param string $requiredPath [explicite description]
	 * @param $group [explicite description]
	 *
	 * @return bool
	 */
	public function changeGroup($group): bool
	{
		return \chgrp($this->getPathname(), $group);
	}

	/**
	 * Method changeOwner
	 *
	 * @param string $requiredPath [explicite description]
	 * @param $user [explicite description]
	 *
	 * @return bool
	 */
	public function changeOwner($user): bool
	{
		return \chown($this->getPathname(), $user);
	}

	/**
	 * filetype — Gets file type
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return mixed
	 */
	public function type() /* string|false */
	{
		return $this->getType();
	}

	/**
	 * Method rename
	 *
	 * @param string $name [explicite description]
	 *
	 * @return self
	 */
	public function rename(string $destination): self
	{
		if (strpos($destination, DIRECTORY_SEPARATOR) !== false) {
			throw new DiscException('New name must not include a path.');
		}

		$source = $this->getPathname();

		if ($this->isDir) {
			disc::directoryRequired($source);
		} else {
			disc::fileRequired($source);
		}

		return $this->move(dirname($source) . DIRECTORY_SEPARATOR . $destination);
	}

	public function move(string $destination): self
	{
		$source = $this->getPathname();

		if ($this->isDir) {
			disc::directoryRequired($source);
		} else {
			disc::fileRequired($source);
		}

		$destination = Disc::resolve($destination);

		if (file_exists($destination)) {
			throw new DiscException('Destination already exists');
		}

		\rename($source, $destination);

		parent::__construct($destination);

		return $this;
	}

	/**
	 * Method copy
	 *
	 * @param string $destination [explicite description]
	 *
	 * @return bool
	 */
	public function copy(string $destination): self
	{
		return ($this->isDir()) ? $this->copyDirectory($destination) : $this->copyFile($destination);
	}

	protected function copyFile(string $destination): self
	{
		$source = $this->getPathname();

		disc::fileRequired($source);

		disc::autoGenMissingDirectory($destination);

		$destination = disc::resolve($destination);

		\copy($source, $destination);

		return new File($destination);
	}

	protected function copyDirectory(string $destination): self
	{
		$source = $this->getPathname();

		Disc::directoryRequired($source);

		$destination = Disc::resolve($destination);

		$this->copyRecursive($source, $destination);

		/* return reference to new directory */
		return new Directory($destination);
	}

	protected function copyRecursive(string $source, string $destination): void
	{
		$dir = opendir($source);

		if (!is_dir($destination)) {
			$this->mkdir($destination, 0777, true);
		}

		while ($file = readdir($dir)) {
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
} /* end class */
