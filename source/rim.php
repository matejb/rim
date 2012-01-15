<?php

/**
 * rim - Remote Image Library
 *
 * @author Matej Baćo <matejbaco@gmail.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
*/

require_once(dirname(__FILE__) . '/libs/ArrayFunctionHelper.php');
require_once(dirname(__FILE__) . '/libs/CurlMulti.php');

class rim
{
	public $profile = false;

	/**
	 * getMultiImageTypeAndSize
	 *
	 * @param array $urls Image urls to fetch, key-es will be preserved
	 * @param array $options array(
	 *	'max_num_of_threads' => (integer) def: 10, // how many threads to use when fetching data
	 *	'time_limit' => (decimal) def: null, // limit total execution time in seconds, 2.34 is 2 seconds and 34 miliseconds
	 *	'callback' => (array) def: null, // callback to call after fetch of each image data
	 *	'curl_connect_timeout' => (integer) def: 2, // curl therad connect timeout
	 *	'curl_timeout' => (integer) def: 3 // curl thread timeout
	 * )
	 */
	public function getMultiImageTypeAndSize($urls, $options = array())
	{
		if (empty($urls) || !is_array($urls))
		{
			throw new Exception('Invalid arguments.');
		}

		$default_options = array(
			'max_num_of_threads' => 10,
			'time_limit' => null,
			'callback' => null,
			'curl_connect_timeout' => 2,
			'curl_timeout' => 3
		);
		$options = ArrayFunctionHelper::arrayMerge($default_options, $options);

		$this->_curlMulti = new CurlMulti();
		$this->_curlMulti->maxThreads = $options['max_num_of_threads'];
		if (!empty($options['time_limit']))
		{
			$this->_curlMulti->timeLimit = $options['time_limit'];
		}

		$this->_curlMulti->defaultCurlThreadOptions = array(
			CURLOPT_RETURNTRANSFER 		=> true,        						// return body
			CURLOPT_HEADER         		=> false,       						// return headers
			CURLOPT_BINARYTRANSFER		=> true,								// raw data
			CURLOPT_FOLLOWLOCATION 		=> true,        						// follow redirects
			CURLOPT_ENCODING       		=> "",          						// handle all encodings
			CURLOPT_USERAGENT     	 	=> "rim_spider",  						// who am i
			CURLOPT_AUTOREFERER   	 	=> true,        						// set referer on redirect
			CURLOPT_CONNECTTIMEOUT	 	=> $options['curl_connect_timeout'],	// timeout on connect
			CURLOPT_TIMEOUT       	 	=> $options['curl_timeout']				// timeout on response
		);

		$images_data = array();
		foreach ($urls as $key => $url)
		{
			$images_data[$key] = array(
				'url' => $url,
				'error' => array()
			);

			if (!empty($options['callback']))
			{
				$images_data[$key]['callback'] = $options['callback'];
			}

			if ($this->profile)
			{
				$images_data[$key]['trace'] = array();
			}

			$this->_getImageData($images_data[$key]);
		}

		$this->_curlMulti->process();

		return $images_data;
	}

	/**
	 * getSingleImageTypeAndSize
	 *
	 * @param array $url Image url to fetch
	 * @param array $options array(
	 *	'time_limit' => (decimal) def: null, // limit total execution time in seconds, 2.34 is 2 seconds and 34 miliseconds
	 *	'callback' => (array) def: null, // callback to call after fetch of each image data
	 *	'curl_connect_timeout' => (integer) def: 2, // curl therad connect timeout
	 *	'curl_timeout' => (integer) def: 3 // curl thread timeout
	 * )
	 */
	public function getSingleImageTypeAndSize($url, $options=array())
	{
		$urls = array($url);

		$default_options = array(
			'time_limit' => null,
			'callback' => null,
			'curl_connect_timeout' => 2,
			'curl_timeout' => 3
		);
		$options = ArrayFunctionHelper::arrayMerge($default_options, $options);

		$this->_curlMulti = new CurlMulti();
		$this->_curlMulti->maxThreads = 1;
		if (!empty($options['time_limit']))
		{
			$this->_curlMulti->timeLimit = $options['time_limit'];
		}
		$this->_curlMulti->defaultCurlThreadOptions = array(
			CURLOPT_RETURNTRANSFER 		=> true,       							// return body
			CURLOPT_HEADER         		=> false,       						// return headers
			CURLOPT_BINARYTRANSFER		=> true,								// raw data
			CURLOPT_FOLLOWLOCATION 		=> true,        						// follow redirects
			CURLOPT_ENCODING       		=> "",          						// handle all encodings
			CURLOPT_USERAGENT     	 	=> "rim_spider",    					// who am i
			CURLOPT_AUTOREFERER   	 	=> true,        						// set referer on redirect
			CURLOPT_CONNECTTIMEOUT	 	=> $options['curl_connect_timeout'],	// timeout on connect
			CURLOPT_TIMEOUT       	 	=> $options['curl_timeout']				// timeout on response
		);

		$data = array(
			'url' => $url
		);
		$this->_getImageData($data);

		$this->_curlMulti->process();

		if (!empty($data['error']))
		{
			return array('error' => $data['error']);
		}

		return $data['image_data'];
	}

	/**
	 * stop
	 *
	 * Stops fetch of images data
	 */
	public function stop()
	{
		$this->_curlMulti->stop();
	}

	/**
	 * _getImageData
	 *
	 * First stop in processing every image.
	*/
	protected function _getImageData(&$data)
	{
		$url = $data['url'];

		if (empty($url))
		{
			$data['error'] = array(
				'code' => 0,
				'description' => 'URL not set'
			);
			$this->_triggerCallback($data);
			return false;
		}

		$options = array(
			CURLOPT_RANGE => '0-1'
		);

		$data['image_data'] = array(
			'type' => null,
			'width' => null,
			'height' => null
		);

		$this->_curlMulti->transfer($url, $options, array($this, '_imageTypeCallback'), $data);
	}

	/**
	 * _imageTypeCallback
	 *
	 * Determinig image type.
	 *
	 * @internal Had to be public so CurlMulti can access it.
	*/
	public function _imageTypeCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details)
	{
		if (empty($recived_data))
		{
			$callback_data['error'] = array(
				'code' => 1,
				'description' => 'URL fetch failed',
				'http_status' => $status_code
			);
			$this->_triggerCallback($callback_data);
			return false;
		}

		$data = substr(bin2hex($recived_data), 0, 4);

		switch ($data)
		{
			case 'ffd8': // jpeg
				{
					$callback_data['image_data']['type'] = 'jpeg';

					$options = array();
					$options[CURLOPT_BUFFERSIZE] = '256';
					$options[CURLOPT_RETURNTRANSFER] = '';
					$options[CURLOPT_WRITEFUNCTION] = array($this, "_jpegTransferCallback");

					$callback_data['streamed_buffer'] = '';

					$this->_curlMulti->transfer($url, $options, array($this, '_jpegReadCallback'), $callback_data);
				}
				break;
			case '4749': // gif
				{
					$callback_data['image_data']['type'] = 'gif';

					$options = array();
					$options[CURLOPT_RANGE] = '6-13';
					$this->_curlMulti->transfer($url, $options, array($this, '_gifReadCallback'), $callback_data);
				}
				break;
			case '8950': // png
				{
					$callback_data['image_data']['type'] = 'png';

					$options = array();
					$options[CURLOPT_RANGE] = '16-23';

					$this->_curlMulti->transfer($url, $options, array($this, '_pngReadCallback'), $callback_data);
				}
				break;
			default: // unknown type
				{
					$callback_data['error'] = array(
						'code' => 2,
						'description' => 'unknown image format',
						'http_status' => $status_code
					);

					$this->_triggerCallback($callback_data);
					return false;
				}
		}
	}

	/**
	 * _jpegTransferCallback
	 *
	 * Curl data write buffer function.
	 * Image dimension are on differnt position within a file
	 * so this function will be called when buffer is ready a jpeg can be composed in memory,
	 * once dimension are known futher transfer of jpeg file ends.
	 *
	 * @internal Had to be public so CurlMulti can access it.
	*/
	public function _jpegTransferCallback($ch, $data)
	{
		$size_of_chunk = mb_strlen($data, '8bit');

		$thread_data =& $this->_curlMulti->getThreadDataByCurlHandler($ch);
		$callback_data =& $thread_data['callback_data'];

		if (!isset($callback_data['streamed_buffer']))
			$callback_data['streamed_buffer'] = '';
		$callback_data['streamed_buffer'] .= $data;

		if (strlen($callback_data['streamed_buffer']) < 2)
		{
			return $size_of_chunk;
		}

		// strip magic marker 0xFFD8
		$operationalStreamedData = substr($callback_data['streamed_buffer'], 2);

		do
		{
			// can I read marker
			if (strlen($operationalStreamedData) < 2)
			{
				return $size_of_chunk;
			}

			$info = unpack('nmarker', $operationalStreamedData);
			$operationalStreamedData = substr($operationalStreamedData, 2);

			// only 0xFFC0 is of interest
			if ($info['marker'] != 0xFFC0)
			{
				// can I read length
				if (strlen($operationalStreamedData) < 2)
				{
					return $size_of_chunk;
				}

				// is block whole
				$info = unpack('nlength', $operationalStreamedData);
				if (strlen($operationalStreamedData) < $info['length'])
				{
					return $size_of_chunk;
				}

				$operationalStreamedData = substr($operationalStreamedData, $info['length']);
				continue;
			}

			// 0xFFC0 marker area

			// can I read length
			if (strlen($operationalStreamedData) < 2)
			{
				return $size_of_chunk;
			}

			// is block whole
			$info = unpack('nlength', $operationalStreamedData);
			if (strlen($operationalStreamedData) < $info['length'])
			{
				return $size_of_chunk;
			}
			$operationalStreamedData = substr($operationalStreamedData, 2);

			// get data
			$info = unpack('Cprecision/nY/nX', $operationalStreamedData);

			$callback_data['image_data']['height'] = $info['Y'];
			$callback_data['image_data']['width'] = $info['X'];

			return 0; // stop reading data from source
		} while (!empty($operationalStreamedData));

		return $size_of_chunk;
	}

	/**
	 * _jpegReadCallback
	 *
	 * Will be called when jpeg data is fetched.
	 *
	 * @internal Had to be public so CurlMulti can access it.
	*/
	public function _jpegReadCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details)
	{
		if (isset($callback_data['streamed_buffer']))
		{
			unset($callback_data['streamed_buffer']);
		}

		if (empty($callback_data['image_data']['width']))
		{
			$callback_data['error'] = array(
				'code' => 3,
				'description' => 'jpeg image format read failed',
				'http_status' => $status_code
			);
			$this->_triggerCallback($callback_data);
			return;
		}

		$this->_triggerCallback($callback_data);
	}

	/**
	 * _gifReadCallback
	 *
	 * Will be called when gif data is fetched.
	 *
	 * @internal Had to be public so CurlMulti can access it.
	*/
	public function _gifReadCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details)
	{
		$imageWH = unpack('vwidth/vheight', $recived_data);

		if (empty($imageWH['width']))
		{
			$callback_data['error'] = array(
				'code' => 3,
				'description' => 'gif image format read failed',
				'http_status' => $status_code
			);
			$this->_triggerCallback($callback_data);
			return;
		}

		$callback_data['image_data']['width'] = $imageWH['width'];
		$callback_data['image_data']['height'] = $imageWH['height'];

		$this->_triggerCallback($callback_data);
	}

	/**
	 * _pngReadCallback
	 *
	 * Will be called when png data is fetched.
	 *
	 * @internal Had to be public so CurlMulti can access it.
	*/
	public function _pngReadCallback($url, $recived_data, $status_code, &$callback_data, $transfer_error_details)
	{
		$imageWH = unpack('Nwidth/Nheight', $recived_data);

		if (empty($imageWH['width']))
		{
			$callback_data['error'] = array(
				'code' => 3,
				'description' => 'png image format read failed',
				'http_status' => $status_code
			);
			$this->_triggerCallback($callback_data);
			return;
		}

		$callback_data['image_data']['width'] = $imageWH['width'];
		$callback_data['image_data']['height'] = $imageWH['height'];

		$this->_triggerCallback($callback_data);
	}

	/**
	 * _triggerCallback
	 *
	 * Will trigger callback in clijent code for every single image.
	*/
	protected function _triggerCallback(&$data)
	{
		if (!isset($data['callback']))
			return;

		$callback = $data['callback'];
		unset($data['callback']);

		if (!empty($callback))
		{
			$params = array();
			$params[] = $data;
			$params[] =& $this;

			call_user_func_array($callback, $params);
		}
	}
}