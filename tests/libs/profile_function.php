<?php

function _getImageAbsoluteUrl($img_url, $doc_url)
{
	// domain absolute
	if (strpos($img_url, '://') !== false)
		return $img_url;

	// short absolute
	if (substr($img_url, 0, 2) == '//')
	{
		$url_parts = parse_url($doc_url);
		if (empty($url_parts))
		{
			die('URL could not be parsed, very bad format!');
		}

		$whole_img_url = $url_parts['scheme'] . ':' . $img_url;

		return $whole_img_url;
	}

	// absolute
	if (substr($img_url, 0, 1) == '/')
	{
		$url_parts = parse_url($doc_url);
		if (empty($url_parts))
		{
			die('URL could not be parsed, very bad format!');
		}

		$whole_img_url = $url_parts['scheme'] . '://' . ((!empty($url_parts['user'])) ? $url_parts['user'] . ':' . $url_parts['pass'] . '@' : '') . $url_parts['host'] . ((!empty($url_parts['port'])) ? ':' . $url_parts['port'] : '') . $img_url;
		return $whole_img_url;
	}

	// relative
	{
		$url_parts = parse_url($doc_url);
		if (empty($url_parts))
		{
			die('URL could not be parsed, very bad format!');
		}

		$img_url_parts = parse_url($img_url);
		if (empty($img_url_parts))
		{
			die('URL could not be parsed, very bad format!');
		}

		$whole_img_url = $url_parts['scheme'] . '://' . ((!empty($url_parts['user'])) ? $url_parts['user'] . ':' . $url_parts['pass'] . '@' : '') . $url_parts['host'] . ((!empty($url_parts['port'])) ? ':' . $url_parts['port'] : '');

		$url_path_str = ltrim($url_parts['path'], '/');
		$img_url_path_str = ltrim($img_url_parts['path'], '/');

		$url_path_arr = explode('/', $url_path_str);
		array_pop($url_path_arr); // zadnji segment je dokument

		$img_url_path_arr = explode('/', $img_url_path_str);

		// za svaki .. sa pocetka img url-a makni jedan segment sa kraja fetchanog url-a i .. sa pocetka img url-a
		$img_url_path_arr_copy = $img_url_path_arr;
		while ($img_url_path_part = array_shift($img_url_path_arr_copy))
		{
			if ($img_url_path_part == '..')
			{
				array_shift($img_url_path_arr);
				array_pop($url_path_arr);
			}
		}

		$url_part_of_whole_img_url = ltrim(implode('/', $url_path_arr), '/');
		if (!empty($url_part_of_whole_img_url)) $url_part_of_whole_img_url = '/' . $url_part_of_whole_img_url;

		$img_url_part_of_whole_img_url = (!empty($img_url_path_arr)) ? ltrim(implode('/', $img_url_path_arr), '/') : '';
		if (!empty($img_url_part_of_whole_img_url)) $img_url_part_of_whole_img_url = '/' . $img_url_part_of_whole_img_url;

		$whole_img_url .= $url_part_of_whole_img_url . $img_url_part_of_whole_img_url;

		if (!empty($img_url_parts['query']))
		{
			$whole_img_url .= '?' . $img_url_parts['query'];
		}

		if (!empty($img_url_parts['fragment']))
		{
			$whole_img_url .= '#' . $img_url_parts['fragment'];
		}

		return $whole_img_url;
	}
}

function _transfer($url)
{
	$ch = curl_init($url);
	if ($ch === false)
	{
		die(__LINE__);
	}

	$default_options = array(
		CURLOPT_RETURNTRANSFER 		=> true,        // return body
		CURLOPT_HEADER         		=> false,       // return headers
		CURLOPT_BINARYTRANSFER		=> true,		// raw data
		CURLOPT_FOLLOWLOCATION 		=> true,        // follow redirects
		CURLOPT_ENCODING       		=> "",          // handle all encodings
		CURLOPT_USERAGENT     	 	=> "spider",    // who am i
		CURLOPT_AUTOREFERER   	 	=> true,        // set referer on redirect
		CURLOPT_CONNECTTIMEOUT	 	=> 3,          	// timeout on connect
		CURLOPT_TIMEOUT       	 	=> 6         	// timeout on response
	);

	curl_setopt_array($ch, $default_options);

	$data = curl_exec($ch);

	$info = curl_getinfo($ch);

	curl_close($ch);

	return $data;
}

function getImageUrls($url)
{
	$html = _transfer($url);


	$_dom = new DOMDocument();
	$_dom->preserveWhiteSpace = false;

	$success = @$_dom->loadHTML($html);

	$dom_images = $_dom->getElementsByTagName('img');
	$num_of_img_in_document = $dom_images->length;
	$images = array();
	for ($i = 0; $i < $num_of_img_in_document; $i++)
	{
		$img_url = $dom_images->item($i)->getAttribute('src');
		$img_url = _getImageAbsoluteUrl($img_url, $url);

		if (isset($img_urls[$img_url]))
		{
			continue;
		}

		$images[$img_url] = $img_url;
	}

	return $images;
}