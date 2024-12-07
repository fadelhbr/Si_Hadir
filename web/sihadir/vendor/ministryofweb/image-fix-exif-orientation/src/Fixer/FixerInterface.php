<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation\Fixer;

use MinistryOfWeb\ImageFixExifOrientation\Image;

interface FixerInterface
{
    /**
     * @param Image $image
     *
     * @return string fixed image binary data
     */
    public function fixOrientation(Image $image): string;
}
