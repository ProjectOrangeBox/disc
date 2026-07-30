<?php

declare(strict_types=1);

namespace orange\disc\disc;

use SplFileInfo;
use orange\disc\Disc;
use orange\disc\exceptions\DiscException;

/**
 * Shared between Disc and File classes
 * (both extend it)
 */

class DiscSplFileInfo extends SplFileInfo
{
    /**
     * Path type (Disc::FILE / Disc::FOLDER) used to select the correct
     * "not found" error when a required path is missing. Subclasses override it.
     */
    protected const PATH_TYPE = 0;

    public function __construct(string $filename)
    {
        parent::__construct(Disc::resolve($filename));
    }

    /**
     * Resolve this entry's path. When $required is true, a missing path raises
     * the file/directory "not found" error chosen by the subclass's PATH_TYPE.
     */
    public function getPath(?bool $required = null, bool $strip = false): string
    {
        $required = ($required === true) ? static::PATH_TYPE : 0;

        return Disc::resolve($this->getPathname(), $strip, $required);
    }

    public function touch(): bool
    {
        return \touch($this->getPath(true));
    }

    /**
     * @return array<string, mixed>|false
     */
    public function info(?string $option = null, mixed $arg1 = null): array|false
    {
        $info = [];

        $absPath = $this->getPath(true);

        $info += \stat($absPath);
        $info += \pathInfo($absPath);

        $info['dirname'] = Disc::resolve($info['dirname'], true);

        $info['type'] = $this->getType();

        $dateFormat = $arg1 ?: 'r';

        $info['atime_display'] = $this->accessTime($dateFormat);
        $info['mtime_display'] = $this->modificationTime($dateFormat);
        $info['ctime_display'] = $this->changeTime($dateFormat);

        $permissions = $this->getPerms();

        $info['permissions_display'] = Disc::formatMode($permissions, DISC::ALL);
        $info['permissions_t'] = Disc::formatMode($permissions, DISC::TYPE);
        $info['permissions_ugw'] = Disc::formatMode($permissions, DISC::PERMISSION);

        $info['uid_display'] = $this->ownerName();
        $info['gid_display'] = $this->groupName();

        $info['size_display'] = Disc::formatSize((int) $this->size());

        $info['isDirectory'] = (bool)$this->isDirectory();
        $info['isWritable'] = (bool)$this->isWritable();
        $info['isReadable'] = (bool)$this->isReadable();
        $info['isFile'] = (bool)$this->isFile();

        $info['root'] = Disc::getRoot();

        if ($option) {
            if (!in_array($option, $info)) {
                throw new DiscException('Unknown option ' . $option);
            }

            $info = $info[$option];
        }

        return $info;
    }

    public function isDirectory(): bool
    {
        return $this->isDir();
    }

    public function directory(): string
    {
        return dirname($this->getPath(true, true));
    }

    public function size(bool $format = false): int|string
    {
        clearstatcache();

        // getSize() returns false when the file cannot be stat'ed
        $size = $this->getSize();

        if ($size === false) {
            throw new DiscException('Could not read the size of "' . $this->getPath(true) . '".');
        }

        return ($format) ? Disc::formatSize($size) : $size;
    }

    public function accessTime(?string $dateFormat = null): int|string
    {
        return Disc::formatTime($this->getATime(), $dateFormat);
    }

    public function changeTime(?string $dateFormat = null): int|string
    {
        return Disc::formatTime($this->getCTime(), $dateFormat);
    }

    public function modificationTime(?string $dateFormat = null): int|string
    {
        return Disc::formatTime($this->getMTime(), $dateFormat);
    }

    /**
     * @return array<array-key, mixed>|int|false
     */
    public function group(): array|int|false
    {
        return $this->getGroup();
    }

    public function groupName(): string
    {
        // getGroup() returns false when the file cannot be stat'ed, and
        // posix_getgrgid() returns false for a gid the system does not know
        $group = $this->group();
        $entry = is_int($group) ? posix_getgrgid($group) : false;

        return $entry === false ? '' : $entry['name'];
    }

    /**
     * @return array<array-key, mixed>|int|false
     */
    public function owner(): array|int|false
    {
        return $this->getOwner();
    }

    public function ownerName(): string
    {
        // getOwner() returns false when the file cannot be stat'ed, and
        // posix_getpwuid() returns false for a uid the system does not know
        $owner = $this->owner();
        $entry = is_int($owner) ? posix_getpwuid($owner) : false;

        return $entry === false ? '' : $entry['name'];
    }

    public function permissions(int $options = 0): int|string
    {
        $rawPerms = $this->getPerms();

        return ($options) ? Disc::formatMode($rawPerms, $options) : (int) octdec(substr(sprintf('%o', $rawPerms), -4));
    }

    public function changePermissions(int $mode): bool
    {
        $oMask = umask(0);

        $rtn = \chmod($this->getPath(true), $mode);

        umask($oMask);

        return $rtn;
    }

    public function changeGroup(string|int $group): bool
    {
        return \chgrp($this->getPath(true), $group);
    }

    public function changeOwner(string|int $user): bool
    {
        return \chown($this->getPath(true), $user);
    }

    public function type(): string|false
    {
        return $this->getType();
    }

    public function rename(string $name): self
    {
        if (str_contains($name, DIRECTORY_SEPARATOR)) {
            throw new DiscException('New name must not include a path. Please use move(...)');
        }

        return $this->move(dirname($this->getPath(true)) . DIRECTORY_SEPARATOR . $name);
    }

    public function move(string $destination): self
    {
        $destination = Disc::resolve($destination);

        if (!is_dir($destination) && file_exists($destination)) {
            throw new DiscException('Destination already exists');
        }

        if (!is_dir($destination)) {
            new Directory($destination)->create();
        }

        \rename($this->getPath(true), $destination);

        parent::__construct($destination);

        return $this;
    }

    public function exists(?string $insideDir = null): bool
    {
        $path = ($insideDir == null) ? $this->getPath() : $this->getPath() . DIRECTORY_SEPARATOR . ltrim($insideDir, DIRECTORY_SEPARATOR);

        return \file_exists($path);
    }
}
