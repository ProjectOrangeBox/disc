<?php

declare(strict_types=1);

namespace orange\disc\disc;

use orange\disc\Disc;
use orange\disc\disc\Export;
use orange\disc\disc\Import;
use orange\disc\disc\DiscSplFileInfo;
use orange\disc\disc\FileSplFileObject;
use orange\disc\exceptions\FileException;

class File extends DiscSplFileInfo
{
    protected const PATH_TYPE = Disc::FILE;

    protected ?FileSplFileObject $fileObject = null;

    public Import $import;
    public Export $export;

    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->import = new import($this);
        $this->export = new export($this);
    }

    /**
     * Method __call
     *
     * @param array<array-key, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        /* throws error on fail */
        if (!$this->fileObject) {
            throw new FileException('No file open');
        }

        if (!method_exists($this->fileObject, $name)) {
            trigger_error(sprintf('Call to undefined function: %s::%s().', static::class, $name), E_USER_ERROR);
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

            Disc::autoGenMissingDirectory($path);
        }

        /* close properly - dropping the last reference closes the handle */
        $this->fileObject = null;

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

        $this->fileObject = null;

        return $this;
    }

    public function name(?string $suffix = null): string
    {
        // SplFileInfo::getBasename — Gets the base name of the file
        // SplFileInfo::getFilename — Gets the filename
        return ($suffix) ? $this->getBasename($suffix) : $this->getFilename();
    }

    /**
     * @param int-mask<FILE_USE_INCLUDE_PATH, FILE_IGNORE_NEW_LINES, FILE_SKIP_EMPTY_LINES, FILE_NO_DEFAULT_CONTEXT> $flags
     * @return list<string>
     */
    public function asArray(int $flags = 0): array
    {
        // file() returns false for a file it cannot read
        $lines = \file($this->getPath(true), $flags);

        if ($lines === false) {
            throw new FileException('Could not read "' . $this->getPath(true) . '".');
        }

        return $lines;
    }

    public function echo(): int
    {
        $bytes = \readfile($this->getPath(true));

        if ($bytes === false) {
            throw new FileException('Could not read "' . $this->getPath(true) . '".');
        }

        return $bytes;
    }

    public function contents(): string
    {
        $contents = \file_get_contents($this->getPath(true));

        if ($contents === false) {
            throw new FileException('Could not read "' . $this->getPath(true) . '".');
        }

        return $contents;
    }

    /**
     * atomicFilePutContents - atomic file_put_contents
     */
    public function save(string $content): int
    {
        /* create absolute path */
        $path = $this->getPath();

        Disc::autoGenMissingDirectory($path);

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

    /* move & rename in DiscSplFileInfo */

    public function mime(): string
    {
        // mime_content_type() returns false when it cannot determine the type
        $mime = mime_content_type($this->getPath(true));

        if ($mime === false) {
            throw new FileException('Could not determine the mime type of "' . $this->getPath(true) . '".');
        }

        return $mime;
    }

    public function isImage(): bool
    {
        return exif_imagetype($this->getPath(true)) !== false;
    }

    public function width(): int
    {
        $path = $this->getPath(true);

        if (exif_imagetype($path) === false) {
            throw new FileException('File "' . $this->getPath(true) . '" is not an image.');
        }

        $details = getimagesize($path);

        if ($details === false) {
            throw new FileException('Could not read the image size of "' . $path . '".');
        }

        return $details[0];
    }

    public function height(): int
    {
        $path = $this->getPath(true);

        if (exif_imagetype($path) === false) {
            throw new FileException('File "' . $this->getPath(true) . '" is not an image.');
        }

        $details = getimagesize($path);

        if ($details === false) {
            throw new FileException('Could not read the image size of "' . $path . '".');
        }

        return $details[1];
    }

    public function datauri(): string
    {
        // contents() already fails loudly on an unreadable file, and this was
        // decoding rather than encoding - a data uri carries base64, so the
        // bytes have to be encoded on the way in
        return 'data:' . $this->mime() . ';base64,' . base64_encode($this->contents());
    }

    public function src(): string
    {
        return Disc::resolveWWW($this->getPath(true), Disc::FILE);
    }

    public function download(?string $differentFilename = null, ?string $differentMime = null): void
    {
        $filename = $differentFilename ?? $this->getFilename();
        $mime = $differentMime ?? $this->mime();

        // fopen() returns false when the file cannot be opened for reading
        $fp = fopen($this->getPath(true), 'rb');

        if ($fp === false) {
            throw new FileException('Could not open "' . $this->getPath(true) . '" for reading.');
        }

        // Clean output buffer
        if (ob_get_level() !== 0 && @ob_end_clean() === false) {
            @ob_clean();
        }

        // Generate the server headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $this->getSize());
        header('Cache-Control: private, no-transform, no-store, must-revalidate');

        // Flush 1MB chunks of data
        while (!feof($fp) && ($data = fread($fp, 1048576)) !== false) {
            echo $data;
        }

        fclose($fp);
        exit;
    }
}
