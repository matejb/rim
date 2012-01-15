<?php

/**
 * CurlMulti
 *
 * @author Matej Baćo <matejbaco@gmail.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
*/

require_once(dirname(__FILE__) . '/ArrayFunctionHelper.php');

class CurlMulti
{
	protected $_curlMultiHandler;

	protected $_processQueue;
	protected $_startTime;
	protected $_stop;

	protected $_threads;
	protected $_urlLookupMap;

	/**
	 * @var integer How many threads to use
	*/
	public $maxThreads = 10;

	/**
	 * @var decimal Limit execution in seconds
	*/
	public $timeLimit = null;

	/**
	 * @var array Default curl thread options
	*/
	public $defaultCurlThreadOptions = array();

	/**
	 * transfer
	 *
	 * Request transfer.
	 *
	 * @param string $url Url to be fetched.
	 * @param array $thread_options Curl thread options.
	 * @param array $callback Callback to be called after transfer or in case of error.
	 * @param array $callback_data Pre existing data for callback.
	*/
	public function transfer($url, $thread_options = array(), $callback = null, &$callback_data = null)
	{
		if (empty($this->maxThreads) || empty($this->defaultCurlThreadOptions))
		{
			throw new Exception('maxThreads or defaultCurlThreadOptions not set');
		}

		$thread_options[CURLOPT_URL] = $url;
		$thread_options = ArrayFunctionHelper::arrayMerge($this->defaultCurlThreadOptions, $thread_options);

		$transfer_data = array(
			'url' => $url,
			'curl_options' => $thread_options,
			'callback' => $callback,
			'callback_data' => &$callback_data
		);
		if (!isset($this->_urlLookupMap[$url]))
		{
			$this->_urlLookupMap[$url] =& $transfer_data;
		}

		$this->_addToProcessQueue($transfer_data);
	}

	/**
	 * process
	 *
	 * Proccess all queued transfer request.
	*/
	public function process()
	{
		$this->_stop = false;
		$this->_startTime = microtime(true);

		if (!$this->_curlMultiHandler)
		{
			$this->_curlMultiHandler = curl_multi_init();
		}

		do
		{
			$this->_fillThreads();

			if ($this->_stop) return;

			$num_of_active_threads = 0;
			$threads_process_status = curl_multi_exec($this->_curlMultiHandler, $num_of_active_threads);

			do
			{
				if ($this->_stop) return;

				$num_of_remaining_msg = 0;
				$thread_data = curl_multi_info_read($this->_curlMultiHandler, $num_of_remaining_msg);

				if ($thread_data)
				{
					if ($thread_data['result'] == CURLE_OK)
					{
						$transfer_error_details = array();
						$recived_data = curl_multi_getcontent($thread_data['handle']);
					}
					else
					{
						$possible_curl_constants = array(
							'CURLE_OK',
							'CURLE_UNSUPPORTED_PROTOCOL',
							'CURLE_FAILED_INIT',
							'CURLE_URL_MALFORMAT',
							'CURLE_URL_MALFORMAT_USER',
							'CURLE_COULDNT_RESOLVE_PROXY',
							'CURLE_COULDNT_RESOLVE_HOST',
							'CURLE_COULDNT_CONNECT',
							'CURLE_FTP_WEIRD_SERVER_REPLY',
							'CURLE_FTP_ACCESS_DENIED',
							'CURLE_FTP_USER_PASSWORD_INCORRECT',
							'CURLE_FTP_WEIRD_PASS_REPLY',
							'CURLE_FTP_WEIRD_USER_REPLY',
							'CURLE_FTP_WEIRD_PASV_REPLY',
							'CURLE_FTP_WEIRD_227_FORMAT',
							'CURLE_FTP_CANT_GET_HOST',
							'CURLE_FTP_CANT_RECONNECT',
							'CURLE_FTP_COULDNT_SET_BINARY',
							'CURLE_PARTIAL_FILE',
							'CURLE_FTP_COULDNT_RETR_FILE',
							'CURLE_FTP_WRITE_ERROR',
							'CURLE_FTP_QUOTE_ERROR',
							'CURLE_HTTP_NOT_FOUND',
							'CURLE_WRITE_ERROR',
							'CURLE_MALFORMAT_USER',
							'CURLE_FTP_COULDNT_STOR_FILE',
							'CURLE_READ_ERROR',
							'CURLE_OUT_OF_MEMORY',
							'CURLE_OPERATION_TIMEOUTED',
							'CURLE_FTP_COULDNT_SET_ASCII',
							'CURLE_FTP_PORT_FAILED',
							'CURLE_FTP_COULDNT_USE_REST',
							'CURLE_FTP_COULDNT_GET_SIZE',
							'CURLE_HTTP_RANGE_ERROR',
							'CURLE_HTTP_POST_ERROR',
							'CURLE_SSL_CONNECT_ERROR',
							'CURLE_FTP_BAD_DOWNLOAD_RESUME',
							'CURLE_FILE_COULDNT_READ_FILE',
							'CURLE_LDAP_CANNOT_BIND',
							'CURLE_LDAP_SEARCH_FAILED',
							'CURLE_LIBRARY_NOT_FOUND',
							'CURLE_FUNCTION_NOT_FOUND',
							'CURLE_ABORTED_BY_CALLBACK',
							'CURLE_BAD_FUNCTION_ARGUMENT',
							'CURLE_BAD_CALLING_ORDER',
							'CURLE_HTTP_PORT_FAILED',
							'CURLE_BAD_PASSWORD_ENTERED',
							'CURLE_TOO_MANY_REDIRECTS',
							'CURLE_UNKNOWN_TELNET_OPTION',
							'CURLE_TELNET_OPTION_SYNTAX',
							'CURLE_OBSOLETE',
							'CURLE_SSL_PEER_CERTIFICATE',
							'CURLE_GOT_NOTHING',
							'CURLE_SSL_ENGINE_NOTFOUND',
							'CURLE_SSL_ENGINE_SETFAILED',
							'CURLE_SEND_ERROR',
							'CURLE_RECV_ERROR',
							'CURLE_SHARE_IN_USE',
							'CURLE_SSL_CERTPROBLEM',
							'CURLE_SSL_CIPHER',
							'CURLE_SSL_CACERT',
							'CURLE_BAD_CONTENT_ENCODING',
							'CURLE_LDAP_INVALID_URL',
							'CURLE_FILESIZE_EXCEEDED',
							'CURLE_FTP_SSL_FAILED'
						);

						$constant = '';
						foreach ($possible_curl_constants as $single_const_name)
						{
							if ($thread_data['result'] == constant($single_const_name))
							{
								$constant = $single_const_name;
								break;
							}
						}

						$transfer_error_details = array(
							'curl_error_number' => $thread_data['result'],
							'curl_error_constant' => $constant
						);
						$recived_data = null;
					}

					$last_http_status_code = curl_getinfo($thread_data['handle'], CURLINFO_HTTP_CODE);

					if ($last_http_status_code < 200 || $last_http_status_code >= 300)
					{
						$recived_data = null;
					}

					$stored_thread_data = $this->_threads[$thread_data['handle']];

					if (isset($stored_thread_data['callback_data']['trace']))
					{
						$stored_thread_data['callback_data']['trace'][] = array(
							'time' => (microtime(true) - $this->_startTime),
							'num_of_threads' => sizeof($this->_threads)
						);
					}

					if (!empty($stored_thread_data['callback']))
					{
						$params = array();
						$params[] = $stored_thread_data['url'];
						$params[] = $recived_data;
						$params[] = $last_http_status_code;
						$params[] =& $stored_thread_data['callback_data'];
						$params[] = $transfer_error_details;
						$params[] =& $this;

						call_user_func_array($stored_thread_data['callback'], $params);
					}

					if ($this->_fillThreads(true))
					{
						$num_of_active_threads++;
					}

					if ($this->_stop) return;

					curl_multi_remove_handle($this->_curlMultiHandler, $thread_data['handle']);
					curl_close($thread_data['handle']);
					unset($this->_threads[$thread_data['handle']]);
				}
			} while ($num_of_remaining_msg > 0);

		} while (!empty($this->_processQueue) || $threads_process_status === CURLM_CALL_MULTI_PERFORM || $num_of_active_threads > 0);

		curl_multi_close($this->_curlMultiHandler);
	}

	/**
	 * stop
	 *
	 * Stops all transfers.
	*/
	public function stop()
	{
		$this->_cleanup();
	}

	/**
	 * getThreadDataByCurlHandler
	 *
	 * Gets storen thread data by curl thread object.
	 *
	 * @param object $curl_handler
	*/
	public function getThreadDataByCurlHandler($curl_handler)
	{
		return (isset($this->_threads[$curl_handler])) ? $this->_threads[$curl_handler] : null;
	}

	/**
	 * numOfThreads
	 *
	 * Current number of working therads.
	*/
	public function numOfThreads()
	{
		return sizeof($this->_threads);
	}

	/**
	 * undoneTransfers
	 *
	 * Transfers wating in queue.
	*/
	public function undoneTransfers()
	{
		return $this->_processQueue;
	}

	/**
	 * _addToProcessQueue
	 *
	 * Add transfer to queue.
	*/
	protected function _addToProcessQueue(&$transfer_data)
	{
		$this->_processQueue[] = &$transfer_data;
	}

	/**
	 * _cleanup
	 *
	 * Rise stop flag for process and reset everything.
	*/
	protected function _cleanup()
	{
		$this->_stop = true;

		foreach ($this->_threads as $thread_handler => $thread_data)
		{
			curl_multi_remove_handle($this->_curlMultiHandler, $thread_data['curl_handler']);
			curl_close($thread_data['curl_handler']);
		}

		curl_multi_close($this->_curlMultiHandler);
	}

	/**
	 * _fillThreads
	 *
	 * Fills working thread pool.
	 *
	 * @param boolean $increase Push addition thread in workig pool.
	*/
	protected function _fillThreads($increase = false)
	{
		if ($this->timeLimit > 0)
		{
			$time_remaning = $this->timeLimit - (microtime(true) - $this->_startTime);
			if ($time_remaning <= 0)
			{
				$this->_cleanup();
				return false;
			}
		}

		$is_filled = false;

		$size_of_threads = sizeof($this->_threads);
		while (
			!empty($this->_processQueue)
			&& (
				($size_of_threads < $this->maxThreads)
				|| (
					$increase
					&& (
						$size_of_threads < ($this->maxThreads + 1)
					)
				)
			)
		)
		{
			if ($this->_stop) return;

			$thread_data =& $this->_processQueue[0];
			array_shift($this->_processQueue);

			$thread_handler = curl_init();
			curl_setopt_array($thread_handler, $thread_data['curl_options']);
			$thread_data['curl_handler'] = $thread_handler;

			$this->_threads[$thread_handler] =& $thread_data;

			curl_multi_add_handle($this->_curlMultiHandler, $thread_handler);
			$is_filled = true;

			$size_of_threads = sizeof($this->_threads);
		}

		return $is_filled;
	}
}