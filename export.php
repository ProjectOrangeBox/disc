<?php

declare(strict_types=1);

namespace dmyers\orange\disc;

use dmyers\orange\disc\File;
use dmyers\orange\disc\exceptions\FileException;

class Export
{
	const JSONFLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

	protected $fileInfo = null;
	protected $path = null;

	public function __construct(File $fileInfo)
	{
		$this->fileInfo = $fileInfo;

		$this->path = $this->fileInfo->getPathname();
	}

	public function convertToStringPhp($input): string
	{
		$string = '';

		if (\is_array($input) || \is_object($input)) {
			$string = '<?php return ' . \str_replace(['Closure::__set_state', 'stdClass::__set_state'], '(object)', \var_export($input, true)) . ';';
		} elseif (\is_scalar($input)) {
			$string = '<?php return "' . \str_replace('"', '\"', $input) . '";';
		} else {
			throw new FileException('Unknown input type.');
		}

		return $string;
	}

	public function convertToStringJson($input, bool $pretty = false, ?int $flags = null, ?int $depth = 512): string
	{
		$flags = ($flags) ?? self::JSONFLAGS;
		$depth = ($depth) ?? 512;

		if ($pretty) {
			$flags = $flags | JSON_PRETTY_PRINT;
		}

		return json_encode($input, $flags, $depth);
	}

	public function convertToStringIni(array $array, array $parent = []): string
	{
		$ini = '';

		foreach ($array as $key => $value) {
			if (\is_array($value)) {
				//subsection case
				//merge all the sections into one array...
				$subsection = \array_merge((array) $parent, (array) $key);
				//add section information to the output
				$ini .= '[' . \join('.', $subsection) . ']' . PHP_EOL;
				//recursively traverse deeper
				$ini .= $this->convertToStringIni($value, $subsection);
			} else {
				//plain key->value case
				$ini .= "$key=$value" . PHP_EOL;
			}
		}

		return $ini;
	}

	public function php($data, ?int $chmod = null): int
	{
		$bytes = $this->changeModeOnBytes(Disc::atomicSaveContent($this->path, $this->convertToStringPhp($data)), $chmod);

		/* if it's cached we need to flush it out so the old one isn't loaded */
		Disc::removePhpFileFromOpcache($this->path);

		return $bytes;
	}

	public function json($jsonObj, ?bool $pretty = false, ?int $flags = null, ?int $depth = 512, ?int $chmod = null): int
	{
		$pretty = ($pretty) ?? false;
		$depth = ($depth) ?? 512;

		return $this->changeModeOnBytes(Disc::atomicSaveContent($this->path, $this->convertToStringJson($jsonObj, $pretty, $flags, $depth)), $chmod);
	}

	public function ini(array $array, ?int $chmod = null): int
	{
		return $this->changeModeOnBytes(Disc::atomicSaveContent($this->path, $this->convertToStringIni($array)), $chmod);
	}

	public function content(string $content, ?int $chmod = null): int
	{
		return $this->changeModeOnBytes(Disc::atomicSaveContent($this->path, $content), $chmod);
	}

	public function csv(array $table, bool $includeHeader = true, string $separator = ",", string $enclosure = "\"", string $escape = "\\", string $eol = "\n")
	{
		$fp = fopen($this->path, 'w');

		foreach ($table as $fields) {
			if ($includeHeader) {
				fputcsv($fp, array_keys($fields), $separator, $enclosure, $escape, $eol);

				$includeHeader = false;
			}
			fputcsv($fp, $fields, $separator, $enclosure, $escape, $eol);
		}

		fclose($fp);
	}

	public function changeModeOnBytes(int $bytes, ?int $chmod): int
	{
		if ($bytes && $chmod) {
			\chmod($this->path, $chmod);
		}

		return $bytes;
	}
} /* end class */
