<?php

declare(strict_types=1);

namespace dmyers\disc;

use dmyers\disc\export;
use dmyers\disc\import;
use dmyers\disc\discSplFileInfo;
use dmyers\disc\fileSplFileObject;
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
	 * @param $name string [file object method name]
	 * @param $arguments [file object method arguments]
	 *
	 * @return mixed
	 */
	public function __call(string $name, $arguments)
	{
		/* throws error on fail */
		if (!$this->fileObject) {
			throw new FileException('No file open');
		}

		if (!method_exists($this->fileObject, $name)) {
			trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
		}

		return $this->fileObject->$name(...$arguments);
	}

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
		$this->fileObject = new fileSplFileObject($path, $mode);

		return $this;
	}

	public function create(string $mode = 'w'): self
	{
		return $this->open($mode);
	}

	public function append(string $mode = 'a'): self
	{
		return $this->open($mode);
	}

	public function close(): self
	{
		if (!$this->fileObject) {
			throw new FileException('No file open');
		}

		unset($this->fileObject);

		return $this;
	}

	public function name(string $suffix = null): string
	{
		return ($suffix) ? $this->getBasename($suffix) : $this->getFilename();
	}

	public function asArray(int $flags = 0): array
	{
		return \file($this->getPath(true), $flags);
	}

	public function echo(): int
	{
		return \readfile($this->getPath(true));
	}

	public function contents(): string
	{
		return \file_get_contents($this->getPath(true));
	}

	/**
	 * atomicFilePutContents - atomic file_put_contents
	 *
	 * @param mixed $content
	 *
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

	public function remove(bool $quiet = false): bool
	{
		unset($this->fileObject);

		$success = false;

		$filename = $this->getPath(!$quiet);

		if (file_exists($filename)) {
			$success = \unlink($filename);
		}

		return $success;
	}

	/* move & rename in DiscSplFileInfo */
} /* end class */
