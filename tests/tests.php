<?php

require_once(dirname(__FILE__) . '/libs/simpletest/autorun.php');
require_once(dirname(__FILE__) . '/../source/rim.php');

class RimTest extends UnitTestCase
{
	/* RIM core functionalty tests */

	public function testImageTypeAndSize()
	{
		$rim = new rim();

		// png 8-bit test
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_8_test.png');
		$expectedData = array(
			'type' => 'png',
			'width' => '450',
			'height' => '320'
		);
		$this->assertEqual($imageData, $expectedData);

		// png 24-bit test
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_24_test.png');
		$expectedData = array(
			'type' => 'png',
			'width' => '450',
			'height' => '320'
		);
		$this->assertEqual($imageData, $expectedData);

		// gif test
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif');
		$expectedData = array(
			'type' => 'gif',
			'width' => '450',
			'height' => '320'
		);
		$this->assertEqual($imageData, $expectedData);

		// jpeg test
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/jpeg_test.jpg');
		$expectedData = array(
			'type' => 'jpeg',
			'width' => '450',
			'height' => '320'
		);
		$this->assertEqual($imageData, $expectedData);

		// small jpeg test
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg');
		$expectedData = array(
			'type' => 'jpeg',
			'width' => '100',
			'height' => '71'
		);
		$this->assertEqual($imageData, $expectedData);
	}

	public function testEmptyURL()
	{
		$rim = new rim();

		// broken jpeg
		$imageData = $rim->getSingleImageTypeAndSize('');
		$this->assertTrue(!empty($imageData['error']));
		$this->assertEqual($imageData['error']['code'], 0);
	}

	public function testBrokenImages()
	{
		$rim = new rim();

		// broken jpeg
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/broken_jpeg_test.jpg');
		$this->assertTrue(!empty($imageData['error']));
		$this->assertEqual($imageData['error']['code'], 3);

		// unknown format
		$imageData = $rim->getSingleImageTypeAndSize('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/corrupted_jpeg_test.jpg');
		$this->assertTrue(!empty($imageData['error']));
		$this->assertEqual($imageData['error']['code'], 2);
	}

	public function testMultiFetchSuccess()
	{
		$rim = new rim();

		$images_data_input = array(
			'png_8_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_8_test.png',
			'png_24_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_24_test.png',
			'gif_test.gif'			=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif',
			'jpeg_test.jpg'		 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/jpeg_test.jpg',
			'small_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg',
		);

		$images_data_expected_output = array(
			'png_8_test.png' 		=> 	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_8_test.png',
											'image_data' => array(
													'type' => 'png',
													'width' => '450',
													'height' => '320'
												),
											'error' => array()
										),
			'png_24_test.png' 		=>	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_24_test.png',
											'image_data' => array(
													'type' => 'png',
													'width' => '450',
													'height' => '320'
												),
											'error' => array()
										),
			'gif_test.gif'			=>	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif',
											'image_data' => array(
													'type' => 'gif',
													'width' => '450',
													'height' => '320'
												),
											'error' => array()
										),
			'jpeg_test.jpg'		 	=>	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/jpeg_test.jpg',
											'image_data' => array(
													'type' => 'jpeg',
													'width' => '450',
													'height' => '320'
												),
											'error' => array()
										),
			'small_jpeg_test.jpg' 	=>	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg',
											'image_data' => array(
													'type' => 'jpeg',
													'width' => '100',
													'height' => '71'
												),
											'error' => array()
										)
		);

		$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input);
		$this->assertEqual($images_data_output, $images_data_expected_output);
	}

	public function testMultiFetchFails()
	{
		$rim = new rim();

		$images_data_input = array(
			'unknown.png' 				=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/unknown.png',
			'broken_jpeg_test.jpg' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/broken_jpeg_test.jpg',
			'corrupted_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/corrupted_jpeg_test.jpg',
		);

		$images_data_expected_output = array(
			'unknown.png' 				=> 	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/unknown.png',
											'image_data' => array(
													'type' => null,
													'width' => null,
													'height' => null
												),
											'error' => array(
												'code' => 1,
												'description' => 'URL fetch failed',
												'http_status' => 404
											)
										),
			'broken_jpeg_test.jpg' 		=>	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/broken_jpeg_test.jpg',
											'image_data' => array(
													'type' => 'jpeg',
													'width' => null,
													'height' => null
												),
											'error' => array(
												'code' => 3,
												'description' => 'jpeg image format read failed',
												'http_status' => 200
											)
										),
			'corrupted_jpeg_test.jpg'	=>	array(
											'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/corrupted_jpeg_test.jpg',
											'image_data' => array(
													'type' => null,
													'width' => null,
													'height' => null
												),
											'error' => array(
												'code' => 2,
												'description' => 'unknown image format',
												'http_status' => 206
											)
										)
		);

		$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input);
		$this->assertEqual($images_data_output, $images_data_expected_output);
	}

	protected $_foundedPictureInTestMultiFetchCallbackMode;
	public function testMultiFetchCallbackMode()
	{
		$rim = new rim();

		$images_data_input = array(
			'png_8_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_8_test.png',
			'png_24_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_24_test.png',
			'gif_test.gif'			=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif',
			'jpeg_test.jpg'		 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/jpeg_test.jpg',
			'small_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg',
		);

		$rim_options = array(
			'max_num_of_threads' => 1,
			'callback' => array($this, '_testMultiFetchCallbackModeCallback')
		);
		$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input, $rim_options);

		$wanted_images_data = array(
			'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif',
			'image_data' => array(
					'type' => 'gif',
					'width' => '450',
					'height' => '320'
				),
			'error' => array()
		);

		$this->assertEqual($this->_foundedPictureInTestMultiFetchCallbackMode, $wanted_images_data);

		$how_many_fully_fetched = 0;
		foreach ($images_data_output as $single_img_data_output)
		{
			if (!empty($single_img_data_output['image_data']['width']))
				$how_many_fully_fetched++;
		}
		$this->assertTrue(sizeof($how_many_fully_fetched) < 5);
	}

	public function _testMultiFetchCallbackModeCallback($data, &$rimObject)
	{
		$waiting_for_images_data = array(
			'url' => 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif',
			'image_data' => array(
					'type' => 'gif',
					'width' => '450',
					'height' => '320'
				),
			'error' => array()
		);

		if ($data == $waiting_for_images_data)
		{
			$rimObject->stop();

			$this->_foundedPictureInTestMultiFetchCallbackMode = $data;
		}
	}

	public function testMultiFetchWithTimeLimit()
	{
		$rim = new rim();

		$images_data_input = array(
			'png_8_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_8_test.png',
			'png_24_test.png' 		=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/png_24_test.png',
			'gif_test.gif'			=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/gif_test.gif',
			'jpeg_test.jpg'		 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/jpeg_test.jpg',
			'small_jpeg_test.jpg' 	=> 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg',
		);

		$rim_options = array(
			'max_num_of_threads' => 1,
			'time_limit' => 1,
			'callback' => array($this, '_testMultiFetchWithTimeLimitCallback')
		);
		$images_data_output = $rim->getMultiImageTypeAndSize($images_data_input, $rim_options);

		$how_many_fully_fetched = 0;
		foreach ($images_data_output as $single_img_data_output)
		{
			if (!empty($single_img_data_output['image_data']['width']))
				$how_many_fully_fetched++;
		}
		$this->assertTrue(sizeof($how_many_fully_fetched) < 5);
	}

	public function _testMultiFetchWithTimeLimitCallback()
	{
		sleep(1);
	}



	/* library CurlMulti test */

	public function testCurlMulti()
	{
		$curlMulti = new CurlMulti();
		$curlMulti->maxThreads = 1;
		$curlMulti->defaultCurlThreadOptions = array(
			CURLOPT_RETURNTRANSFER 		=> true,     	// return body
			CURLOPT_HEADER         		=> true,      	// return headers
			CURLOPT_BINARYTRANSFER		=> false,		// raw data
			CURLOPT_FOLLOWLOCATION 		=> true,        // follow redirects
			CURLOPT_ENCODING       		=> "",          // handle all encodings
			CURLOPT_USERAGENT     	 	=> "spider",    // who am i
			CURLOPT_AUTOREFERER   	 	=> true,        // set referer on redirect
			CURLOPT_CONNECTTIMEOUT	 	=> 3,          	// timeout on connect
			CURLOPT_TIMEOUT       	 	=> 6         	// timeout on response
		);

		$data_expected_in_callback = array(
			'test' => 'TeSt'
		);
		$curlMulti->transfer('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg', array(), array($this, '_testCurlMultiCallback'), $data_expected_in_callback);

		$curlMulti->process();
	}

	public function _testCurlMultiCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details)
	{
		$this->assertEqual($url, 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg');
		$this->assertEqual($callback_data, array('test' => 'TeSt'));
		$this->assertEqual($status_code, 200);
		$this->assertEqual($transfer_error_details, array());
	}

	public function testCurlMultiCap()
	{
		$curlMulti = new CurlMulti();
		$curlMulti->defaultCurlThreadOptions = array(
			CURLOPT_RETURNTRANSFER 		=> true,        // return body
			CURLOPT_HEADER         		=> false,       // return headers
			CURLOPT_BINARYTRANSFER		=> false,		// raw data
			CURLOPT_FOLLOWLOCATION 		=> true,        // follow redirects
			CURLOPT_ENCODING       		=> "",          // handle all encodings
			CURLOPT_USERAGENT     	 	=> "spider",    // who am i
			CURLOPT_AUTOREFERER   	 	=> true,        // set referer on redirect
			CURLOPT_CONNECTTIMEOUT	 	=> 3,          	// timeout on connect
			CURLOPT_TIMEOUT       	 	=> 6         	// timeout on response
		);

		$curlMulti->maxThreads = 3;

		$call_back_data = array();
		for ($i = 1; $i <= 10; $i++)
		{
			$curlMulti->transfer('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg' . '?t=' . $i, array(), array($this, '_testCurlMultiCapCallback'), $call_back_data);
		}

		$curlMulti->process();
	}

	public function _testCurlMultiCapCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details, &$curlMultiObject)
	{
		$this->assertTrue($curlMultiObject->numOfThreads() <= 3);
	}

	public function testCurlMultiTimeCap()
	{
		$curlMulti = new CurlMulti();
		$curlMulti->defaultCurlThreadOptions = array(
			CURLOPT_RETURNTRANSFER 		=> true,        // return body
			CURLOPT_HEADER         		=> false,       // return headers
			CURLOPT_BINARYTRANSFER		=> false,		// raw data
			CURLOPT_FOLLOWLOCATION 		=> true,        // follow redirects
			CURLOPT_ENCODING       		=> "",          // handle all encodings
			CURLOPT_USERAGENT     	 	=> "spider",    // who am i
			CURLOPT_AUTOREFERER   	 	=> true,        // set referer on redirect
			CURLOPT_CONNECTTIMEOUT	 	=> 3,          	// timeout on connect
			CURLOPT_TIMEOUT       	 	=> 6         	// timeout on response
		);
		$curlMulti->maxThreads = 1;
		$curlMulti->timeLimit = 1;

		$call_back_data = array();
		for ($i = 1; $i <= 3; $i++)
		{
			$curlMulti->transfer('http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/tests_resources/small_jpeg_test.jpg' . '?t=' . $i, array(), array($this, '_testCurlMultiTimeCapCallback'), $call_back_data);
		}

		$curlMulti->process();

		$undone_transfers = $curlMulti->undoneTransfers();
		$this->assertTrue(sizeof($undone_transfers) > 1);
	}

	public function _testCurlMultiTimeCapCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details, &$curlMultiObject)
	{
		sleep(1);
	}



	/* library ArrayFunctionHelper test */

	public function testArrayFunctionHelper()
	{
		$array_1 = array(
			'a_1',
			'k_a_1' => 'v_1'
		);
		$array_2 = array(
			'a_1',
			'k_a_1' => 'v_2',
			'k_b_2' => 'v_2',
			'd_2'
		);

		require_once(dirname(__FILE__) . '/../source/libs/ArrayFunctionHelper.php');
		$result_array = ArrayFunctionHelper::arrayMerge($array_1, $array_2);

		$expected_result = array(
			'a_1',
			'k_a_1' => 'v_2',
			'k_b_2' => 'v_2',
			'd_2'
		);

		$this->assertEqual($result_array, $expected_result);
	}
}

?>