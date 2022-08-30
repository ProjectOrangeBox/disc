<?php

declare(strict_types=1);

namespace dmyers\disc;

use SplFileObject;

class fileSplFileObject extends SplFileObject
{
	/* wrappers for "f" methods */

	public function characters(int $length) /* string|false */
	{
		return $this->fread($length);
	}

	public function write(string $string, ?int $length = null) /* int|false */
	{
		return ($length) ? $this->fileObject->fwrite($string, $length) : $this->fileObject->fwrite($string);
	}

	public function writeLine(string $string, string $lineEnding = null)
	{
		$lineEnding = ($lineEnding) ?? PHP_EOL;

		return $this->write($string . $lineEnding);
	}

	public function character() /* string|false */
	{
		return $this->characters(1);
	}

	public function line(): string
	{
		return $this->fileObject->fgets();
	}

	public function lock(int $operation, int &$wouldBlock = null): bool
	{
		return $this->fileObject->flock($operation, $wouldBlock);
	}

	public function position(int $position = null): int
	{
		return ($position) ? $this->fileObject->fseek($this->handle, $position) : $this->fileObject->ftell($this->handle);
	}

	public function flush(): bool
	{
		return $this->fileObject->fflush();
	}
} /* end class */
