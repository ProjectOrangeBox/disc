<?php

declare(strict_types=1);

namespace dmyers\disc;

use SplFileObject;
use dmyers\disc\export;
use dmyers\disc\import;
use dmyers\disc\discSplFileInfo;
use dmyers\disc\exceptions\FileException;

class File extends DiscSplFileInfo
{
	const TYPE = 'file';

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
		if (in_array($mode, ['r', 'r+'])) {
			/* required file */
			$path = $this->getPath(true);
		} else {
			/* file not required */
			$path = $this->getPath();

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
		return \file($this->getPath(true), $flags);
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
		return \readfile($this->getPath(true));
	}

	/**
	 * Method content
	 * 
	 * read entire file and return
	 *
	 * @return string
	 */
	public function get(): string
	{
		return \file_get_contents($this->getPath(true));
	}

	/**
	 * atomicFilePutContents - atomic file_put_contents
	 *
	 * @param string $path
	 * @param mixed $content
	 * @return int returns the number of bytes that were written to the file.
	 */
	public function save(string $content): int
	{
		/* create absolute path */
		$path = $this->getPath();

		disc::autoGenMissingDirectory($path);

		/* get the path where you want to save this file so we can put our file in the same directory */
		$directory = \dirname($path);

		/* is this directory writeable */
		if (!is_writable($directory)) {
			throw new fileException($directory . ' is not writable.');
		}

		/* create a temporary file with unique file name and prefix */
		$temporaryFile = \tempnam($directory, 'afpc_');

		/* did we get a temporary filename */
		if ($temporaryFile === false) {
			throw new fileException('Could not create temporary file ' . $temporaryFile . '.');
		}

		/* write to the temporary file */
		$bytes = \file_put_contents($temporaryFile, $content, LOCK_EX);

		/* did we write anything? */
		if ($bytes === false) {
			throw new fileException('No bytes written by file_put_contents');
		}

		/* move it into place - this is the atomic function */
		if (\rename($temporaryFile, $path) === false) {
			throw new fileException('Could not rename temporary file ' . $temporaryFile . ' ' . $path . '.');
		}

		/* return the number of bytes written */
		return $bytes;
	}

	protected function copy(string $destination): self
	{
		$destination = Disc::resolve($destination);

		if (file_exists($destination)) {
			throw new FileException('Destination already exsists');
		}

		disc::autoGenMissingDirectory($destination);

		\copy($this->getPath(true), $destination);

		return new File($destination);
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

		$filename = $this->getPath();

		if (file_exists($filename)) {
			$success = \unlink($filename);
		}

		return $success;
	}

	/* move & rename in DiscSplFileInfo */

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
