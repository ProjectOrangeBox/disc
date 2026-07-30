<?php

declare(strict_types=1);

namespace orange\disc\disc;

use orange\disc\Disc;
use orange\disc\disc\File;
use orange\disc\exceptions\FileException;

class Export
{
    public const JSONDEFAULT = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    public const JSONPRETTY = JSON_PRETTY_PRINT;
    protected string $path;

    public function __construct(protected File $fileInfo)
    {
        $this->path = $this->fileInfo->getPath();
    }

    public function php2String(mixed $input): string
    {
        $string = '';

        if (\is_array($input) || \is_object($input)) {
            $string = '<?php return ' . \str_replace(['Closure::__set_state', 'stdClass::__set_state'], '(object)', \var_export($input, true)) . ';';
        } elseif (\is_scalar($input)) {
            $string = '<?php return "' . \str_replace('"', '\"', (string) $input) . '";';
        } else {
            throw new FileException('Unknown input type.');
        }

        return $string;
    }

    public function json2String(mixed $input, bool $pretty = false, ?int $flags = null, ?int $depth = 512): string
    {
        $flags ??= self::JSONDEFAULT;
        $depth ??= 512;

        if ($pretty) {
            $flags = $flags | JSON_PRETTY_PRINT;
        }

        // json_encode() returns false on failure and this returns string;
        // its $depth must be at least 1
        return json_encode($input, $flags | JSON_THROW_ON_ERROR, max(1, $depth));
    }

    /**
     * @param array<array-key, mixed> $array
     * @param array<array-key, mixed> $parent
     */
    public function ini2String(array $array, array $parent = []): string
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
                $ini .= $this->ini2String($value, $subsection);
            } else {
                //plain key->value case
                $ini .= "$key=$value" . PHP_EOL;
            }
        }

        return $ini;
    }

    public function php(mixed $data, ?int $chmod = null): int
    {
        $bytes = $this->changeModeOnBytes($this->fileInfo->save($this->php2String($data)), $chmod);

        /* if it's cached we need to flush it out so the old one isn't loaded */
        $this->removePhpFileFromOpcache($this->path);

        return $bytes;
    }

    public function removePhpFileFromOpcache(string $path): bool
    {
        return (\function_exists('opcache_invalidate')) ? \opcache_invalidate(Disc::resolve($path), true) : true;
    }

    public function json(mixed $jsonObj, ?bool $pretty = false, ?int $flags = null, ?int $depth = 512, ?int $chmod = null): int
    {
        $pretty ??= false;
        $depth ??= 512;

        return $this->changeModeOnBytes($this->fileInfo->save($this->json2String($jsonObj, $pretty, $flags, $depth)), $chmod);
    }

    /**
     * @param array<array-key, mixed> $array
     */
    public function ini(array $array, ?int $chmod = null): int
    {
        return $this->changeModeOnBytes($this->fileInfo->save($this->ini2String($array)), $chmod);
    }

    public function content(string $content, ?int $chmod = null): int
    {
        return $this->changeModeOnBytes($this->fileInfo->save($content), $chmod);
    }

    /**
     * @param array<array-key, mixed> $table
     */
    public function csv(array $table, bool $includeHeader = true, string $separator = ",", string $enclosure = "\"", string $escape = "\\", string $eol = "\n"): bool
    {
        // fopen() returns false when the path cannot be opened for writing
        $fp = fopen($this->path, 'w');

        if ($fp === false) {
            throw new FileException('Could not open "' . $this->path . '" for writing.');
        }

        foreach ($table as $fields) {
            if ($includeHeader) {
                fputcsv($fp, array_keys($fields), $separator, $enclosure, $escape, $eol);

                $includeHeader = false;
            }
            fputcsv($fp, $fields, $separator, $enclosure, $escape, $eol);
        }

        return fclose($fp);
    }

    protected function changeModeOnBytes(int $bytes, ?int $chmod): int
    {
        if ($bytes && $chmod) {
            \chmod($this->path, $chmod);
        }

        return $bytes;
    }
}
