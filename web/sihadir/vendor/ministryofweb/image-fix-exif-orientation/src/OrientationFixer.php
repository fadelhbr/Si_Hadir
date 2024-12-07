<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation;

use MinistryOfWeb\ImageFixExifOrientation\Fixer\FixerInterface;
use MinistryOfWeb\ImageFixExifOrientation\Output\OutputInterface;

class OrientationFixer
{
    /**
     * @var FixerInterface
     */
    private $fixer;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param FixerInterface $fixer
     * @param OutputInterface $output
     */
    public function __construct(FixerInterface $fixer, OutputInterface $output)
    {
        $this->fixer = $fixer;
        $this->output = $output;
    }

    /**
     * @param string $imageFile
     *
     * @return mixed
     */
    public function fix(Image $image)
    {
        return $this->output->output($this->fixer->fixOrientation($image));
    }
}
