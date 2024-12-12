<?php

declare(strict_types=1);

namespace unit\Fixer;

use MinistryOfWeb\ImageFixExifOrientation\Fixer\Gd;
use MinistryOfWeb\ImageFixExifOrientation\Image;
use PHPUnit\Framework\TestCase;

class GdTest extends TestCase
{
    /**
     * @var string
     */
    private $fixturesDirectory;

    /**
     * @var string
     */
    private $tmpDirectory;

    /**
     * @var Gd
     */
    private $fixer;

    public function setUp(): void
    {
        parent::setUp();

        $this->fixturesDirectory = dirname(__DIR__, 2) . '/fixtures';
        $this->tmpDirectory = dirname(__DIR__, 2) . '/tmp';
        $this->fixer = new Gd(100);
    }

    public function testFixLandscapeImages(): void
    {
        for ($index = 0; $index < 9; $index++) {
            $image = new Image($this->fixturesDirectory . '/Landscape_' . $index . '.jpg');
            $targetFile = $this->tmpDirectory . '/FIXED_GD_Landscape_' . $index . '.jpg';
            file_put_contents($targetFile, $this->fixer->fixOrientation($image));
            self::assertFileExists($targetFile);
            // @todo find a way to test if the actual image orientation is as expected
        }
    }

    public function testFixPortraitImages(): void
    {
        for ($index = 0; $index < 9; $index++) {
            $image = new Image($this->fixturesDirectory . '/Portrait_' . $index . '.jpg');
            $targetFile = $this->tmpDirectory . '/FIXED_GD_Portrait_' . $index . '.jpg';
            file_put_contents($targetFile, $this->fixer->fixOrientation($image));
            self::assertFileExists($targetFile);
            // @todo find a way to test if the actual image orientation is as expected
        }
    }
}
