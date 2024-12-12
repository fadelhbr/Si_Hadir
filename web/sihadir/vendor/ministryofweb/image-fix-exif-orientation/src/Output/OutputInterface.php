<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation\Output;

interface OutputInterface
{
    /**
     * @param string $imageData
     *
     * @return mixed
     */
    public function output(string $imageData);
}
