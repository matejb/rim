<?php

require_once(dirname(__FILE__) . '/../source/rim.php');

$rim = new rim();



// get one image

$image_url = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/png_8_test.png';
$image_data = $rim->getSingleImageTypeAndSize($image_url);

echo "\n\n<br /><br /><hr />[line " . __LINE__ . "] get one image <br />\n";
echo '<img src="' . $image_url . '">' . "<br />\n";
echo '<pre>';
var_dump($image_data);
echo '</pre>';



// get broken image

$image_url = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/broken_jpeg_test.jpgg';
$image_data = $rim->getSingleImageTypeAndSize($image_url);

echo "\n\n<br /><br /><hr />[line " . __LINE__ . "] get broken image <br />\n";
echo '<img src="' . $image_url . '">' . "<br />\n";
echo '<pre>';
var_dump($image_data);
echo '</pre>';



// mutiple images

$images_data_input = array(
	'png_8_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/png_8_test.png',
	'png_24_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/png_24_test.png',
	'gif_test.gif'			=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/gif_test.gif',
	'jpeg_test.jpg'		 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/jpeg_test.jpg',
	'small_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/small_jpeg_test.jpg',
);

$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input);

echo "\n\n<br /><br /><hr />[line " . __LINE__ . "] mutiple images <br />\n";
foreach ($images_data_output as $image_data)
{
	echo '<img src="' . $image_data['url'] . '">' . "<br />\n";
	echo '<pre>';
	var_dump($image_data);
	echo '</pre>';
}



// mutiple images

$images_data_input = array(
	'png_8_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/png_8_test.png',
	'png_24_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/png_24_test.png',
	'gif_test.gif'			=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/gif_test.gif',
	'jpeg_test.jpg'		 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/jpeg_test.jpg',
	'small_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/small_jpeg_test.jpg',
);

$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input);

echo "\n\n<br /><br /><hr />[line " . __LINE__ . "] mutiple images <br />\n";
foreach ($images_data_output as $image_data)
{
echo '<img src="' . $image_data['url'] . '">' . "<br />\n";
echo '<pre>';
var_dump($image_data);
echo '</pre>';
}



// failed mutiple images

$images_data_input = array(
	'unknown.png' 				=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests//tests_resources/unknown.png',
	'broken_jpeg_test.jpg' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests//tests_resources/broken_jpeg_test.jpg',
	'corrupted_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests//tests_resources/corrupted_jpeg_test.jpg',
);

$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input);

echo "\n\n<br /><br /><hr />[line " . __LINE__ . "] failed mutiple images <br />\n";
foreach ($images_data_output as $image_data)
{
	echo '<img src="' . $image_data['url'] . '">' . "<br />\n";
	echo '<pre>';
	var_dump($image_data);
	echo '</pre>';
}



// mutiple images with callback

$images_data_input = array(
	'png_8_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/png_8_test.png',
	'gif_test.gif'			=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/gif_test.gif',
	'jpeg_test.jpg'		 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/../tests/tests_resources/jpeg_test.jpg'
);

$rim_options = array(
	'max_num_of_threads' => 1, // how many threads to use, def. 10
	'time_limit' => 2.3, // time limit in seconds, def. no time limit
	'callback' => 'multiple_images_callback', // callback function, if inside object use array($object, 'callback_method')
	'curl_connect_timeout' => 2, // curl therad connect timeout in seconds, def. 2
	'curl_timeout' => 3, // curl thread timeout in seconds, def 3
	'curl_buffer_size' => 2056 // use 2056 bytes as buffer size in fetching jpeg images, default value is 256
);

echo "\n\n<br /><br /><hr />[line " . __LINE__ . "] mutiple with callback mode <br />\n";

$break_on_image_type = 'gif';

$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input, $rim_options);

foreach ($images_data_output as $image_data)
{
	echo '<img src="' . $image_data['url'] . '">' . "<br />\n";
	echo '<pre>';
	var_dump($image_data);
	echo '</pre>';
}

function multiple_images_callback($data, &$rimObject)
{
	global $break_on_image_type;

	echo "multiple_images_callback called for " . $data['url'] . " image<br />\n";

	if ($data['image_data']['type'] == $break_on_image_type)
	{
		echo "multiple_images_callback braking<br />\n";
		$rimObject->stop();
	}
}

?>