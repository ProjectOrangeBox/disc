<?php

declare(strict_types=1);

namespace dmyers\disc;

use SplFileInfo;
use dmyers\disc\exceptions\DiscException;

/**
 * Shared between Disc and File classes
 * (both extend it)
 */

class DiscSplFileInfo extends SplFileInfo
{
	public function touch(): bool
	{
		return \touch($this->getPath(true));
	}

	public function info(?string $option = null, $arg1 = null) /* array|false */
	{
		$info = [];

		$absPath = $this->getPath(true);

		$info += \stat($absPath);
		$info += \pathInfo($absPath);

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


		$info['uid_display'] = $this->ownerName();
		$info['gid_display'] = $this->groupName();

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

	public function isDirectory(): bool
	{
		return $this->isDir();
	}

	public function directory(): string
	{
		return dirname($this->getPath(true, true));
	}

	public function size(): int
	{
		clearstatcache();

		return $this->getSize();
	}

	public function accessTime(string $dateFormat = null) /* int|string */
	{
		return disc::formatTime($this->getATime(), $dateFormat);
	}

	public function changeTime(string $dateFormat = null) /* int|string */
	{
		return disc::formatTime($this->getCTime(), $dateFormat);
	}

	public function modificationTime(string $dateFormat = null) /* int|string */
	{
		return disc::formatTime($this->getMTime(), $dateFormat);
	}

	public function group() /* array|int|false */
	{
		return $this->getGroup();
	}

	public function groupName() /* array|int|false */
	{
		return posix_getgrgid($this->group())['name'];
	}

	public function owner() /* array|int|false */
	{
		return $this->getOwner();
	}

	public function ownerName() /* array|int|false */
	{
		return posix_getpwuid($this->owner())['name'];
	}

	public function permissions(int $options = 0)
	{
		return ($options) ? disc::formatPermissions($this->getPerms(), $options) : $this->getPerms();
	}

	public function changePermissions(int $mode): bool
	{
		return \chmod($this->getPath(true), $mode);
	}

	public function changeGroup($group): bool
	{
		return \chgrp($this->getPath(true), $group);
	}

	public function changeOwner($user): bool
	{
		return \chown($this->getPath(true), $user);
	}

	public function type() /* string|false */
	{
		return $this->getType();
	}

	public function rename(string $name): self
	{
		if (strpos($name, DIRECTORY_SEPARATOR) !== false) {
			throw new DiscException('New name must not include a path. Please use move(...)');
		}

		return $this->move(dirname($this->getPath(true)) . DIRECTORY_SEPARATOR . $name);
	}

	public function move(string $destination): self
	{
		$destination = Disc::resolve($destination);

		if (file_exists($destination)) {
			throw new DiscException('Destination already exists');
		}

		if (!is_dir($destination)) {
			(new Directory($destination))->create();
		}

		\rename($this->getPath(true), $destination);

		parent::__construct($destination);

		return $this;
	}

	public function exists(string $insideDir = null): bool
	{
		$path = ($insideDir == null) ? $this->getPath() : $this->getPath() . DIRECTORY_SEPARATOR . ltrim($insideDir, DIRECTORY_SEPARATOR);

		return \file_exists($path);
	}

	public function getPath(bool $required = null, bool $strip = false): string
	{
		$required = ($required === true) ? static::TYPE : '';

		return disc::resolve($this->getPathname(), $strip, $required);
	}
} /* end class */
