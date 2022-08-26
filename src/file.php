<?php

declare(strict_types=1);

namespace dmyers\orange\disc;

use SplFileInfo;
use SplFileObject;
use dmyers\orange\disc\export;
use dmyers\orange\disc\import;
use dmyers\orange\disc\exceptions\FileException;

class File extends SplFileInfo
{
	protected $fileObject = null;

	public $import = null;
	public $export = null;

	public function __construct(string $path)
	{
		parent::__construct($path);

		$this->import = new import($this);
		$this->export = new export($this);
	}

	/**
	 * Method __call
	 *
	 * @param $name $name [explicite description]
	 * @param $arguments $arguments [explicite description]
	 *
	 * @return void
	 */
	public function __call($name, $arguments)
	{
		$this->requireOpenFile(); /* throws error on fail */

		if (method_exists($this->fileObject, $name)) {
			return $this->fileObject->$name(...$arguments);
		}

		trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
	}

	/**
	 * Method open
	 *
	 * @param string $mode [explicite description]
	 *
	 * @return self
	 */
	public function open(string $mode = 'r'): self
	{
		$path = Disc::resolve($this->getPathname());

		if (is_dir($path)) {
			throw new FileException(Disc::resolve($this->getPathname(), true) . ' is a Directory');
		}

		if (in_array($mode, ['r', 'r+'])) {
			Disc::fileRequired($path);
		} else {
			Disc::autoGenMissingDirectory($path);
		}

		/* close properly */
		if ($this->fileObject) {
			$this->fileObject = null;
		}

		$this->fileObject = new SplFileObject($path, $mode);

		return $this;
	}

	/**
	 * Method create
	 *
	 * @param string $mode [explicite description]
	 *
	 * @return self
	 */
	public function create(string $mode = 'w'): self
	{
		return $this->open($mode);
	}

	/**
	 * Method append
	 *
	 * @param string $mode [explicite description]
	 *
	 * @return self
	 */
	public function append(string $mode = 'a'): self
	{
		return $this->open($mode);
	}

	/**
	 * Method close
	 *
	 * @return self
	 */
	public function close(): self
	{
		$this->requireOpenFile(); /* throws error on fail */

		$this->fileObject = null;

		return $this;
	}

	/**
	 * Method write
	 * 
	 * Write to file
	 *
	 * @param string $string [explicite description]
	 * @param ?int $length [explicite description]
	 *
	 * @return void
	 */
	public function write(string $string, ?int $length = null) /* int|false */
	{
		$this->requireOpenFile(); /* throws error on fail */

		return ($length) ? $this->fileObject->fwrite($string, $length) : $this->fileObject->fwrite($string);
	}

	/**
	 * Method writeLine
	 * 
	 * Write to file with line feed
	 *
	 * @param string $string [explicite description]
	 * @param string $lineEnding [explicite description]
	 *
	 * @return void
	 */
	public function writeLine(string $string, string $lineEnding = null)
	{
		$lineEnding = ($lineEnding) ?? PHP_EOL;

		return $this->write($string . $lineEnding);
	}

	/**
	 * Method character
	 * 
	 * Read single character from file
	 *
	 * @return void
	 */
	public function character() /* string|false */
	{
		return $this->characters(1);
	}

	/**
	 * Method characters
	 * 
	 * Read 1 or more characters from file
	 *
	 * @param int $length [explicite description]
	 *
	 * @return void
	 */
	public function characters(int $length) /* string|false */
	{
		return $this->fileObject->fread($length);
	}

	/**
	 * Method line
	 * 
	 * Read line from file
	 * auto detecting line ending
	 *
	 * @return string
	 */
	public function line(): string
	{
		$this->requireOpenFile(); /* throws error on fail */

		return $this->fileObject->fgets();
	}

	/**
	 * Method lock
	 * 
	 * Lock file
	 *
	 * @param int $operation [explicite description]
	 * @param int $wouldBlock [explicite description]
	 *
	 * @return bool
	 */
	public function lock(int $operation, int &$wouldBlock = null): bool
	{
		$this->requireOpenFile(); /* throws error on fail */

		return $this->fileObject->flock($operation, $wouldBlock);
	}

	/**
	 * Method position
	 *
	 * @param int $position [explicite description]
	 *
	 * @return int
	 */
	public function position(int $position = null): int
	{
		$this->requireOpenFile(); /* throws error on fail */

		return ($position) ? $this->fileObject->fseek($this->handle, $position) : $this->fileObject->ftell($this->handle);
	}

	/**
	 * Method flush
	 *
	 * @return bool
	 */
	public function flush(): bool
	{
		$this->requireOpenFile(); /* throws error on fail */

		return $this->fileObject->fflush();
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
	 * Method filename
	 *
	 * @param string $suffix [explicite description]
	 *
	 * @return string
	 */
	public function filename(string $suffix = null): string
	{
		return ($suffix) ? $this->getBasename($suffix) : $this->getFilename();
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
		return Disc::resolve($this->getPath(), true);
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
		return self::formatTime($this->getATime(), $dateFormat);
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
		return self::formatTime($this->getCTime(), $dateFormat);
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
		return self::formatTime($this->getMTime(), $dateFormat);
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
		return ($options) ? Disc::formatPermissions($this->getPerms(), $options) : $this->getPerms();
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
		return \chmod($this->getRealPath(), $mode);
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
		return \chgrp($this->getRealPath(), $group);
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
		return \chown($this->getRealPath(), $user);
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
	 * info — Gives information about a file
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return mixed
	 */
	public function info(?string $option = null, $arg1 = null) /* array|false */
	{
		$info = [];

		$info += \stat($this->getRealPath());
		$info += \pathInfo($this->getRealPath());

		$info['dirname'] = Disc::resolve($info['dirname'], true);

		$info['type'] = $this->getType();

		$dateFormat = ($arg1) ? $arg1 : 'r';

		$info['atime_display'] = $this->accessTime($dateFormat);
		$info['mtime_display'] = $this->modificationTime($dateFormat);
		$info['ctime_display'] = $this->changeTime($dateFormat);

		$permissions = $this->getPerms();

		$info['permissions_display'] = Disc::formatPermissions($permissions, 3);
		$info['permissions_t'] = Disc::formatPermissions($permissions, 1);
		$info['permissions_ugw'] = Disc::formatPermissions($permissions, 2);


		$info['uid_display'] = $this->owner(true)['name'];
		$info['gid_display'] = $this->group(true)['name'];

		$info['size_display'] = Disc::formatSize($this->size());

		$info['isDirectory'] = (bool)$this->isDirectory();
		$info['isWritable'] = (bool)$this->isWritable();
		$info['isReadable'] = (bool)$this->isReadable();
		$info['isFile'] = (bool)$this->isFile();

		$info['root'] = Disc::getRoot();

		if ($option) {
			if (!in_array($option, $info)) {
				throw new FileException('Unknown option ' . $option);
			}

			$info = $info[$option];
		}

		return $info;
	}

	/**
	 * file — Reads entire file into an array
	 *
	 * @param string $path [path to file/directory]
	 * @param int $flags
	 *
	 * @return mixed
	 */
	public function asArray(int $flags = 0): array
	{
		return \file($this->getRealPath(), $flags);
	}

	/**
	 * Reads a file and writes it to the output buffer.
	 *
	 * @param string $path [path to file/directory]
	 *
	 * @return mixed
	 */
	public function echo(): int
	{
		return \readfile($this->getRealPath());
	}

	/**
	 * Method content
	 * 
	 * read entire file and return
	 *
	 * @return string
	 */
	public function content(): string
	{
		return \file_get_contents($this->getPathname());
	}

	/**
	 * Method touch
	 *
	 * @return bool
	 */
	public function touch(): bool
	{
		return \touch($this->getRealPath());
	}

	/**
	 * Method move
	 *
	 * @param string $name [explicite description]
	 *
	 * @return self
	 */
	public function move(string $name): self
	{
		return Disc::renameFile($this, $name);
	}

	/**
	 * Method rename
	 *
	 * @param string $name [explicite description]
	 *
	 * @return self
	 */
	public function rename(string $name): self
	{
		return self::move($name);
	}

	/**
	 * Method remove
	 *
	 * @return bool
	 */
	public function remove(): bool
	{
		if ($this->fileObject) {
			$this->fileObject = null;
		}

		return \unlink($this->getRealPath());
	}

	/**
	 * Method copy
	 *
	 * @param string $destination [explicite description]
	 *
	 * @return bool
	 */
	public function copy(string $destination): bool
	{
		Disc::autoGenMissingDirectory($destination);

		return \copy($this->getRealPath(), Disc::resolve($destination));
	}

	/**
	 * Method formatTime
	 *
	 * @param $timestamp [explicite description]
	 * @param string $dateFormat [explicite description]
	 *
	 * @return void
	 */
	public function formatTime(?int $timestamp, string $dateFormat) /* int|string */
	{
		return ($timestamp && $dateFormat) ? date($dateFormat, $timestamp) : $timestamp;
	}

	/**
	 * Method requireOpenFile
	 *
	 * @return void
	 */
	protected function requireOpenFile()
	{
		if (!$this->fileObject) {
			throw new FileException('No file open');
		}
	}
} /* end class */
