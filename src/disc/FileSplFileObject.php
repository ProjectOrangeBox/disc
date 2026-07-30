<?php

declare(strict_types=1);

namespace orange\disc\disc;

use SplFileObject;

class FileSplFileObject extends SplFileObject
{
    /* wrappers for "f" methods */

    public function characters(int $length): string|false
    {
        return $this->fread($length);
    }

    public function write(string $string, ?int $length = null): int|false
    {
        return ($length) ? $this->fwrite($string, $length) : $this->fwrite($string);
    }

    public function writeLine(string $string, ?string $lineEnding = null): int|false
    {
        $lineEnding ??= PHP_EOL;

        return $this->write($string . $lineEnding);
    }

    public function character(): string|false
    {
        return $this->characters(1);
    }

    public function line(): string
    {
        return $this->fgets();
    }

    /**
     * @param int<0, 7> $operation one of the LOCK_* constants, optionally
     *     combined with LOCK_NB
     */
    public function lock(int $operation, ?int &$wouldBlock = null): bool
    {
        return $this->flock($operation, $wouldBlock);
    }

    public function position(?int $position = null): int
    {
        // fseek() returns 0 or -1, ftell() returns false on failure
        if ($position !== null && $position !== 0) {
            return $this->fseek($position);
        }

        $current = $this->ftell();

        return $current === false ? -1 : $current;
    }

    public function flush(): bool
    {
        return $this->fflush();
    }
}
