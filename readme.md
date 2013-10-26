rim
============================

Remote Image Library for PHP is tool to get image types and size of remote images in optimized way.

In PHP common way of getting remote image size is using [getimagesize](http://php.net/manual/en/function.getimagesize.php) function.
Function getimagesize is painfully slow because first whole image needs to be downloaded and that takes time and bandwidth.

For every image rim fetches only few bytes need to determine her type and size also library uses multi thread curl compatibilities so images data are fetched in parallel!

Supported image types are: JPEG, GIF, PNG

Licensed under the [GNU Lesser General Public Licence version 3](http://www.gnu.org/licenses/lgpl-3.0.txt)


Usage
-----

Quick examples:

    $rim = new rim();

	// single image
    $image_data = $rim->getSingleImageTypeAndSize('http://domain/path_to_png_file.png');

	// this will $image_data contain
    $image_data = array(
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

	// this will $images_data contain
	$images_data = array(
		'png_image' => 	array(
							'url' => 'http://domain/path_to_png_image.png',
							'image_data' => array(
									'type' => 'png',
									'width' => '450',
									'height' => '220'
								),
							'error' => array()
						),
		'gif_image' => 	array(
							'url' => 'http://domain/path_to_gif_image.gif',
							'image_data' => array(
									'type' => 'gif',
									'width' => '110',
									'height' => '110'
								),
							'error' => array()
						),
		'jpg_image' => 	array(
							'url' => 'http://domain/path_to_jpeg_image.jpg',
							'image_data' => array(
									'type' => 'png',
									'width' => '250',
									'height' => '120'
								),
							'error' => array()
						)
	);

See [examples](/MatejB/rim/blob/master/examples/examples.php) and [tests](/MatejB/rim/blob/master/tests/tests.php) for detailed usage examples.


Performance
-----------

Fetching image types and sizes on [Hot New Releases in Books](http://www.amazon.com/gp/new-releases/books/ref=sv_b_2) page on amazon.com

![Time taken to fetch images](https://raw.github.com/MatejB/rim/master/tests/tests_resources/performance.jpg)