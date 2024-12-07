<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation\Fixer;

use Imagick;
use ImagickException;
use ImagickPixel;
use MinistryOfWeb\ImageFixExifOrientation\Image;
use RuntimeException;

class ImageMagick implements FixerInterface
{
    /**
     * @var int
     */
    private $jpegQuality;

    /**
     * Gd constructor.
     */
    public function __construct(int $jpegQuality = 85)
    {
        $this->jpegQuality = $jpegQuality;
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException when an ImageMagick transformation returned false
     */
    public function fixOrientation(Image $image): string
    {
        $imagick = new Imagick($image->getImageFile());
        $orientation = $image->getExifOrientation();

        $success = true;

        switch ($orientation) {
            case 1:
                // do nothing, everything is fine already
                break;
            case 2:
                $success = $imagick->flopImage();
                break;
            case 3:
                $success = $imagick->rotateImage(new ImagickPixel('#000'), 180);
                break;
            case 4:
                $success = $imagick->flipImage();
                break;
            case 5:
                $success = $imagick->flopImage();
                if (true === $success) {
                    $success = $imagick->rotateImage(new ImagickPixel('#000'), -90);
                }
                break;
            case 6:
                $success = $imagick->rotateImage(new ImagickPixel('#000'), 90);
                break;
            case 7:
                $success = $imagick->flopImage();
                if (true === $success) {
                    $success = $imagick->rotateImage(new ImagickPixel('#000'), 90);
                }
                break;
            case 8:
                $success = $imagick->rotateImage(new ImagickPixel('#000'), -90);
                break;
        }

        if (false === $success) {
            throw new RuntimeException('Cannot transform image', 4603121943);
        }

        $imagick->setImageCompressionQuality($this->jpegQuality);

        return $imagick->getImageBlob();
    }
}
