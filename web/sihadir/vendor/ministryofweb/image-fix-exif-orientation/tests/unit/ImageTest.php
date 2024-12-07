<?php

declare(strict_types=1);

namespace MinistryOfWeb\ImageFixExifOrientation;

use MinistryOfWeb\ImageFixExifOrientation\Exception\InvalidFileTypeException;
use MinistryOfWeb\ImageFixExifOrientation\Output\Filesystem;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @var string
     */
    private $fixturesDirectory;

    public function setUp(): void
    {
        parent::setUp();

        $this->fixturesDirectory = dirname(__DIR__) . '/fixtures';
    }

    public function testDetectOrientationInvalidFileTypePng()
    {
        $this->expectException(InvalidFileTypeException::class);
        $image = new Image($this->fixturesDirectory . '/no-exif.png');
    }

    public function testDetectOrientationInvalidFileTypeGif()
    {
        $this->expectException(InvalidFileTypeException::class);
        $image = new Image($this->fixturesDirectory . '/no-exif.gif');
    }

    public function testDetectOrientationMissingExifData()
    {
        $image = new Image($this->fixturesDirectory . '/no-exif.jpg');
        self::assertFalse($image->hasExifOrientation());
    }

    public function testDetectOrientation(): void
    {
        $image = new Image($this->fixturesDirectory . '/Landscape_0.jpg');
        self::assertEquals(0, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_1.jpg');
        self::assertEquals(1, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_2.jpg');
        self::assertEquals(2, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_3.jpg');
        self::assertEquals(3, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_4.jpg');
        self::assertEquals(4, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_5.jpg');
        self::assertEquals(5, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_6.jpg');
        self::assertEquals(6, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_7.jpg');
        self::assertEquals(7, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Landscape_8.jpg');
        self::assertEquals(8, $image->getExifOrientation());

        $image = new Image($this->fixturesDirectory . '/Portrait_0.jpg');
        self::assertEquals(0, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_1.jpg');
        self::assertEquals(1, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_2.jpg');
        self::assertEquals(2, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_3.jpg');
        self::assertEquals(3, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_4.jpg');
        self::assertEquals(4, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_5.jpg');
        self::assertEquals(5, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_6.jpg');
        self::assertEquals(6, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_7.jpg');
        self::assertEquals(7, $image->getExifOrientation());
        $image = new Image($this->fixturesDirectory . '/Portrait_8.jpg');
        self::assertEquals(8, $image->getExifOrientation());
    }
}
