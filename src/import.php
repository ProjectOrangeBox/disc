<?php

declare(strict_types=1);

namespace dmyers\orange\disc;

use dmyers\orange\disc\File;
use dmyers\orange\disc\exceptions\FileException;

class Import
{
	protected $fileInfo = null;
	protected $path = null;

	public function __construct(File $fileInfo)
	{
		$this->fileInfo = $fileInfo;

		$this->path = $this->fileInfo->getPathname();
	}

	protected function fileExists()
	{
		/* file is required for importing */
		if (!file_exists($this->path)) {
			throw new FileException('File ' . Disc::resolve($this->path, true) . ' not found');
		}
	}

	/**
	 * Method php
	 *
	 * @return void
	 */
	public function php()
	{
		$this->fileExists();

		return include $this->path;
	}

	/**
	 * Method json
	 *
	 * @param bool $associative [explicite description]
	 * @param int $depth [explicite description]
	 * @param int $flags [explicite description]
	 *
	 * @return void
	 */
	public function json(bool $associative = false, int $depth = 512, int $flags = 0)
	{
		$this->fileExists();

		$associative = ($associative) ?? false;
		$depth = ($depth) ?? 512;
		$flags = ($flags) ?? 0;

		$json = json_decode(file_get_contents($this->path), $associative, $depth, $flags);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new FileException('JSON file "' . Disc::resolve($this->path, true) . '" is not valid JSON.');
		}

		return $json;
	}

	/**
	 * Method ini
	 *
	 * @param bool $processSections [explicite description]
	 * @param int $scannerMode [explicite description]
	 *
	 * @return void
	 */
	public function ini(bool $processSections = null, int $scannerMode = null)
	{
		$this->fileExists();

		$processSections = ($processSections) ?? true;
		$scannerMode = ($scannerMode) ?? INI_SCANNER_NORMAL;

		$ini = false;

		$ini = \parse_ini_file($this->path, $processSections, $scannerMode);

		if (!$ini) {
			throw new FileException('INI file "' . Disc::resolve($this->path, true) . '" is not valid.');
		}

		return $ini;
	}

	public function content()
	{
		$this->fileExists();

		return \file_get_contents($this->path);
	}

	public function csv(bool $includeHeader = true, string $separator = ",", string $enclosure = '"', string $escape = "\\"): array
	{
		$this->fileExists();

		$table = [];
		$keys = null;
		$handle = fopen($this->path, 'r');

		while (($columns = fgetcsv($handle, 8192, $separator, $enclosure, $escape)) !== FALSE) {
			if ($includeHeader) {
				$keys = $columns;
				$includeHeader = false;
			} else {
				if (!$keys) {
					for ($idx = 0; $idx <= count($columns); $idx++) {
						$keys[$idx] = $idx;
					}
				}

				$table[] = array_combine($keys, $columns);
			}
		}

		fclose($handle);

		return $table;
	}
} /* end class */