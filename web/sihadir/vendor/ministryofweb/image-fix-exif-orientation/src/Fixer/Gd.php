<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation\Fixer;

use MinistryOfWeb\ImageFixExifOrientation\Image;
use RuntimeException;

class Gd implements FixerInterface
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
     * {@inheritdoc}
     *
     * @throws RuntimeException when the image manipulation failed
     */
    public function fixOrientation(Image $image): string
    {
        ini_set('display_errors', '1');

        $imageResource = imagecreatefromjpeg($image->getImageFile());
        $orientation = $image->getExifOrientation();

        $success = true;

        switch ($orientation) {
            case 1:
                // do nothing, everything is fine already
                break;
            case 2:
                /** @psalm-suppress UndefinedConstant */
                $success = imageflip($imageResource, IMG_FLIP_HORIZONTAL);

                break;
            case 3:
                $imageResource = imagerotate($imageResource, 180, 0);

                break;
            case 4:
                /** @psalm-suppress UndefinedConstant */
                $success = imageflip($imageResource, IMG_FLIP_VERTICAL);

                break;
            case 5:
                /** @psalm-suppress UndefinedConstant */
                $success = imageflip($imageResource, IMG_FLIP_HORIZONTAL);
                if (true === $success) {
                    $imageResource = imagerotate($imageResource, 90, 0);
                }

                break;
            case 6:
                $imageResource = imagerotate($imageResource, -90, 0);

                break;
            case 7:
                /** @psalm-suppress UndefinedConstant */
                $success = imageflip($imageResource, IMG_FLIP_HORIZONTAL);
                if ($success) {
                    $imageResource = imagerotate($imageResource, -90, 0);
                }

                break;
            case 8:
                $imageResource = imagerotate($imageResource, 90, 0);

                break;
        }

        if (!$this->isValidImage($imageResource) || false === $success) {
            throw new RuntimeException('Cannot transform image', 7654091860);
        }

        ob_start();
        imagejpeg($imageResource, null, $this->jpegQuality);
        $imageData = ob_get_clean();

        imagedestroy($imageResource);

        return $imageData;
    }

    /**
     * @param resource|\GdImage $image
     * @return bool
     *
     * @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress UndefinedClass
     */
    private function isValidImage($image): bool
    {
        if (is_resource($image)) {
            return true;
        }

        if (class_exists(\GdImage::class) && $image instanceof \GdImage) {
            return true;
        }

        return false;
    }
}
