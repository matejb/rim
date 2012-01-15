rim
============================

Remote Image Library for PHP is tool to get image types and sizes of remote images in optimized way.

For every image only few bytes are requested need to determine her type and size.
Also rim uses multi thread curl compabilites so images data are fetched in parallel.

Licensed under the [GNU Lesser General Public Licence version 3](http://www.gnu.org/licenses/lgpl-3.0.txt)


Usage
-----

Quick examples:

    $rim = new rim();

	// single image
    $imageData = $rim->getSingleImageTypeAndSize('http://domain/path_to_png_file.png');

	// this will $imageData contain
    $imageData = array(
        'type' => 'png',
        'width' => '450',
        'height' => '320'
    );

	// multiple image fetch
	$images_data_input = array(
		'png_image' => 'http://domain/path_to_png_image.png',
		'gif_image'	=> 'http://domain/path_to_gif_image.gif',
		'jpg_image' => 'http://domain/path_to_jpeg_image.jpg'
	);

	// rim options
	$rim_options = array(
		'max_num_of_threads' => 3, // how many threads to use, 10 is default
	);
	$images_data = $rim->getMultiImageTypeAndSize($images_data_input, $rim_options);

See [tests] for detailed usage examples.


Performance
-----------

Fetching image types and sizes on [Hot New Releases in Books](http://www.amazon.com/gp/new-releases/books/ref=sv_b_2) page on amazon.com

![Time taken to fetch images](/MatejB/rim/raw/master/tests/tests_resources/performance.jpg)