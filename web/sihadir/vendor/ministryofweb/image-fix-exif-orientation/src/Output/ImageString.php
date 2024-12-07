<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation\Output;

class ImageString implements OutputInterface
{
    /**
     * @param string $imageData
     *
     * @return string
     */
    public function output(string $imageData): string
    {
        return $imageData;
    }
}
