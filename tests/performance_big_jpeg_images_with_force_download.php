<?php

set_time_limit(0);
error_reporting(E_ALL);

$collected_data = array();

$image_urls = array();
if ($dir_handler = opendir(dirname(__FILE__) . '/tests_resources/big_images'))
{
	while (false !== ($entry = readdir($dir_handler)))
	{
		$file_path = dirname(__FILE__) . '/tests_resources/big_images/' . $entry;

		if (is_file($file_path))
		{
			$http_image_url = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/libs/force_download.php?name=' . urlencode($entry);
			$image_urls[$http_image_url] = $http_image_url;

			$collected_data['classic_size'][$http_image_url] = filesize($file_path);
			$collected_data['classic_num_of_chunks'][$http_image_url] = 1;
		}
	}
}

// rest of the code

require_once(dirname(__FILE__) . '/libs/profile_function.php');
require_once(dirname(__FILE__) . '/../source/rim.php');

$collected_data['num_of_images'] = sizeof($image_urls);

// classic method
$start_time = microtime(true);
foreach ($image_urls as $img_url)
{
	$img_size = getimagesize($img_url);

	if (!$img_size)
		continue;

	$collected_data['classic'][$img_url] = (microtime(true) - $start_time);
}

// one thread rim
$rim = new rim();
$rim->profile = true;
$rim_options = array(
	'max_num_of_threads' => 1,
	'curl_buffer_size' => 256
);

$images_data = $rim->getMultiImageTypeAndSize($image_urls, $rim_options);
foreach ($images_data as $img_data)
{
	if (!empty($img_data['error']))
		continue;

	$collected_data['rim_1_num_of_chunks'][$img_data['url']] = sizeof($img_data['downloaded_size_trace']);

	$last_trace_data = array_pop($img_data['trace']); // becouse image can be fetched in several steps
	$collected_data['rim_1_thread'][$img_data['url']] = $last_trace_data['time'];

	$last_size = array_pop($img_data['downloaded_size_trace']);
	$collected_data['rim_1_size'][$img_data['url']] = $last_size[0];
}

// 10 thread rim
$rim_options = array(
	'max_num_of_threads' => 10,
	'curl_buffer_size' => 3072
);

$images_data = $rim->getMultiImageTypeAndSize($image_urls, $rim_options);
foreach ($images_data as $img_data)
{
	if (!empty($img_data['error']))
		continue;

	$collected_data['rim_10_num_of_chunks'][$img_data['url']] = sizeof($img_data['downloaded_size_trace']);

	$last_trace_data = array_pop($img_data['trace']); // becouse image can be fetched in several steps
	$collected_data['rim_10_thread'][$img_data['url']] = $last_trace_data['time'];

	// average_threads_used
	$sum_threads_used = 0;
	foreach ($img_data['trace'] as $trace)
	{
		$sum_threads_used += $trace['num_of_threads'];
	}
	$average_threads_used = ($sum_threads_used / sizeof($img_data['trace']));

	$collected_data['rim_10_threads_used'][$img_data['url']] = $average_threads_used;


	$last_size = array_pop($img_data['downloaded_size_trace']);
	$collected_data['rim_10_size'][$img_data['url']] = $last_size[0];
}

//echo '<pre>';
//var_dump($collected_data);
//echo '</pre>';

?>
<html>
	<head>
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("visualization", "1", {packages:["corechart"]});
			google.setOnLoadCallback(drawCharts);
			function drawCharts() {
				// time taken
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Image');
				data.addColumn('number', 'Classic way');
				data.addColumn('number', 'RIM 1 thread limit');
				data.addColumn('number', 'RIM 10 threads limit');
				data.addRows([
				<?php
					$first = true;

					foreach ($collected_data['classic'] as $url => $seconds)
					{
						if (!$first)
							echo ',';

						echo "['$url', $seconds, " . $collected_data['rim_1_thread'][$url] . ", " . $collected_data['rim_10_thread'][$url] . "]";

						$first = false;
					}
				?>
				]);

				var options = {
				width: 700, height: 400,
				title: 'Time taken to fetch images'
				};

				var chart = new google.visualization.LineChart(document.getElementById('time_taken'));
				chart.draw(data, options);


				// therads used
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Image');
				data.addColumn('number', 'Threads used');
				data.addRows([
				<?php
					$first = true;

					foreach ($collected_data['rim_10_threads_used'] as $url => $average_threads_used)
					{
						if (!$first)
							echo ',';

						echo "['$url', " . $average_threads_used . "]";

						$first = false;
					}
				?>
				]);

				var options = {
				width: 700, height: 400,
				title: 'Threads used in RIM 10 threads limit'
				};

				var chart = new google.visualization.LineChart(document.getElementById('threads_used'));
				chart.draw(data, options);


				// downloaded size
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Image');
				data.addColumn('number', 'Classic way size');
				data.addColumn('number', 'RIM 1 thread limit size');
				data.addColumn('number', 'RIM 10 threads limit size');
				data.addRows([
					<?php
						$first = true;

						foreach ($collected_data['classic'] as $url => $seconds)
						{
							if (!$first)
								echo ',';

							echo "['$url', " . $collected_data['classic_size'][$url] . ", " . $collected_data['rim_1_size'][$url] . ", " . $collected_data['rim_10_size'][$url] . "]";

							$first = false;
						}
					?>
				]);

				var options = {
					width: 700, height: 400,
					title: 'Downloaded size comparison in bytes'
				};

				var chart = new google.visualization.LineChart(document.getElementById('downloaded_size'));
				chart.draw(data, options);


				// num of chunks
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Image');
				data.addColumn('number', 'Classic way num http requests');
				data.addColumn('number', 'RIM 1 thread limit num http requests');
				data.addColumn('number', 'RIM 10 threads limit num http requests');
				data.addRows([
					<?php
						$first = true;

						foreach ($collected_data['classic'] as $url => $seconds)
						{
							if (!$first)
								echo ',';

							echo "['$url', " . $collected_data['classic_num_of_chunks'][$url] . ", " . $collected_data['rim_1_num_of_chunks'][$url] . ", " . $collected_data['rim_10_num_of_chunks'][$url] . "]";

							$first = false;
						}
					?>
				]);

				var options = {
					width: 700, height: 400,
					title: 'Number od http requests'
				};

				var chart = new google.visualization.LineChart(document.getElementById('num_of_chunks'));
				chart.draw(data, options);
			}
	  </script>
	</head>
	<body>
		<div id="time_taken"></div>
		<div id="threads_used"></div>
		<div id="downloaded_size"></div>
		<div id="num_of_chunks"></div>
	</body>
</html>