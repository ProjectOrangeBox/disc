<?php

declare(strict_types=1);

namespace dmyers\disc;

use SplFileObject;
use dmyers\disc\export;
use dmyers\disc\import;
use dmyers\disc\discSplFileInfo;
use dmyers\disc\exceptions\FileException;

class File extends discSplFileInfo
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
		$path = disc::resolve($this->getPathname());

		if (is_dir($path)) {
			throw new FileException(disc::resolve($this->getPathname(), true) . ' is a Directory');
		}

		if (in_array($mode, ['r', 'r+'])) {
			disc::fileRequired($path);
		} else {
			disc::autoGenMissingDirectory($path);
		}

		/* close properly */
		unset($this->fileObject);

		/* make a new one */
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

		unset($this->fileObject);

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
		$this->requireOpenFile(); /* throws error on fail */

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
		$this->requireOpenFile(); /* throws error on fail */

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
		$this->requireOpenFile(); /* throws error on fail */

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
	 * Method filename
	 *
	 * @param string $suffix [explicite description]
	 *
	 * @return string
	 */
	public function name(string $suffix = null): string
	{
		return ($suffix) ? $this->getBasename($suffix) : $this->getFilename();
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
		return \file($this->getPathname(), $flags);
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
		return \readfile($this->getPathname());
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
	 * Method remove
	 *
	 * @return bool
	 */
	public function remove(): bool
	{
		unset($this->fileObject);

		$success = false;

		$filename = $this->getPathname();

		if (file_exists($filename)) {
			$success = \unlink($filename);
		}

		return $success;
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
