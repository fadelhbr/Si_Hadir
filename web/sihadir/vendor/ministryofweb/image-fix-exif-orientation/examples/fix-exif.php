<?php

declare(strict_types=1);

use MinistryOfWeb\ImageFixExifOrientation\Fixer\Gd;
use MinistryOfWeb\ImageFixExifOrientation\Fixer\ImageMagick;
use MinistryOfWeb\ImageFixExifOrientation\Image;
use MinistryOfWeb\ImageFixExifOrientation\OrientationFixer;
use MinistryOfWeb\ImageFixExifOrientation\Output\Filesystem;
use MinistryOfWeb\ImageFixExifOrientation\Output\ImageString;

// please initialize Composer vendor directory first (by running `composer install`)
require_once dirname(__DIR__) . '/vendor/autoload.php';

// (1) load existing image from file system; it's possible to initialize an Image
// instance from a string as well
$image = new Image(dirname(__DIR__) . '/tests/fixtures/Landscape_3.jpg');
//$image = Image::fromString($imageData);

// (2) initialize Fixer class, can be either GD oder ImageMagick,
// depending on what image manipulation extension is preferred.
$fixer = new Gd(90);
//$fixer = new ImageMagick(90);

// (3) initialize output class, in this case we write to a temporary file
// in the file system; getting the image data as a string is also possible
$output = new Filesystem(tempnam(sys_get_temp_dir(), 'ministryofweb-exif-fix') . '.jpg');
//$output = new ImageString();

// (4) initialize the fixer
$fixer = new OrientationFixer($fixer, $output);

// (5) fix the image
$fixer->fix($image);

echo 'Fixed image: ' . $output->getFile() . PHP_EOL;
