<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation\Output;

use RuntimeException;

class Filesystem implements OutputInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * Filesystem constructor.
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @param string $imageData
     *
     * @throws RuntimeException when the file cannot be opened for writing
     * @throws RuntimeException when writing to the file failed
     */
    public function output(string $imageData): void
    {
        $fileHandle = fopen($this->file, 'wb');

        if (false === $fileHandle) {
            throw new RuntimeException('Cannot open file for writing', 9263897511);
        }

        $bytesWritten = fwrite($fileHandle, $imageData);
        fclose($fileHandle);

        if (false === $bytesWritten) {
            throw new RuntimeException('Cannot write image file', 3191934000);
        }
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }
}
