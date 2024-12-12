# Automatically Fix Image Orientation

This library automatically rotates an image based on its orientation stored in Exif data. Supports GD and ImageMagick/IMagick.

## Requirements

- PHP 7.3+ compiled with `--enable-exif`
- at least one of the GD or IMagick extensions

## License

This library is licensed under the MIT License.

## Installation

``` shell
composer require mjaschen/image-fix-exif-orientation
```

## Usage

The following example is also located in the `examples` sub-directory and can directly be run from there.

``` php
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
```

## Development

Run unit tests with:

``` shell
./vendor/bin/phpunit
```

There are some tests which actually work with test images and create corrected versions. It's possible to inspect those images after running the tests by opening the `tests/fixtures/fixtures.html` with a web browser.

There are two sets of images (landscape, portrait), each set provides one image for each of the nine possible Exif orientation values (0-8). So in total there are 18 test images.

### Test Images

For testing the images from Dave Perret (<https://github.com/recurser/exif-orientation-examples>) are used. They're licensed under the MIT License.
