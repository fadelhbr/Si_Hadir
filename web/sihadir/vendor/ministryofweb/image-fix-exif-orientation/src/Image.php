<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation;

use MinistryOfWeb\ImageFixExifOrientation\Exception\CannotReadExifDataException;
use MinistryOfWeb\ImageFixExifOrientation\Exception\InvalidFileTypeException;
use RuntimeException;

class Image
{
    private const SUPPORTED_FILE_TYPES = [
        'image/jpeg',
        'image/tiff',
    ];

    /**
     * @var string
     */
    private $imageFile;

    /**
     * @param string $imageFile
     *
     * @throws InvalidFileTypeException when the file type isn't supported (i.e.
     * when it's neither a JPEG nor a TIFF file)
     */
    public function __construct(string $imageFile)
    {
        if (!$this->isValidFileType($imageFile)) {
            throw new InvalidFileTypeException('File type is not supported', 1573467445);
        }

        $this->imageFile = $imageFile;
    }

    /**
     * @param string $imageData
     *
     * @return Image
     *
     * @throws RuntimeException when the temporary file cannot be created
     */
    public static function fromString(string $imageData): Image
    {
        $tempFileName = tempnam(sys_get_temp_dir(), 'ministryofweb-fix-exif');

        if (false === $tempFileName) {
            throw new RuntimeException('Cannot create temporary file', 4669345064);
        }

        $fileHandle = fopen($tempFileName, 'wb');
        fwrite($fileHandle, $imageData);
        fclose($fileHandle);

        return new self($tempFileName);
    }

    /**
     * @return string
     */
    public function getImageFile(): string
    {
        return $this->imageFile;
    }

    /**
     * @return int|null
     *
     * @throws CannotReadExifDataException when reading the Exif data failed
     * @throws RuntimeException when the file is not readable
     */
    public function getExifOrientation(): ?int
    {
        if (!is_readable($this->imageFile)) {
            throw new RuntimeException('File not readable: ' . $this->imageFile, 4601118349);
        }

        $exifData = exif_read_data($this->imageFile);

        if (false === $exifData) {
            throw new CannotReadExifDataException('Cannot read Exif data from file: ' . $this->imageFile, 4966022491);
        }

        if (empty($exifData['Orientation'])) {
            return null;
        }

        $orientation = (int)$exifData['Orientation'];

        if ($orientation < 1 || $orientation > 8) {
            return null;
        }

        return $orientation;
    }

    /**
     * @return bool
     */
    public function hasExifOrientation(): bool
    {
        return null !== $this->getExifOrientation();
    }

    /**
     * @return bool
     */
    private function isValidFileType(string $imageFile): bool
    {
        $finfo = new \finfo();
        $mimeType = $finfo->file($imageFile, FILEINFO_MIME_TYPE);

        return in_array($mimeType, self::SUPPORTED_FILE_TYPES, true);
    }
}
