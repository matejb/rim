<?php

// url to parse for images
$document_url = 'http://www.amazon.com/gp/new-releases/books/ref=sv_b_2';


// rest of the code

require_once(dirname(__FILE__) . '/libs/profile_function.php');
require_once(dirname(__FILE__) . '/../source/rim.php');

$collected_data = array();
$collected_data['document_url'] = $document_url;

$image_urls = getImageUrls($document_url);

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
	'max_num_of_threads' => 1
);

$images_data = $rim->getMultiImageTypeAndSize($image_urls, $rim_options);
foreach ($images_data as $img_data)
{
	if (!empty($img_data['error']))
		continue;

	$last_trace_data = array_pop($img_data['trace']); // becouse image can be fetched in several steps
	$collected_data['rim_1_thread'][$img_data['url']] = $last_trace_data['time'];
}

// 10 thread rim
$rim_options = array(
	'max_num_of_threads' => 10
);

$images_data = $rim->getMultiImageTypeAndSize($image_urls, $rim_options);
foreach ($images_data as $img_data)
{
	if (!empty($img_data['error']))
		continue;

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
}

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
			}
	  </script>
	</head>
	<body>
		<div id="time_taken"></div>
		<div id="threads_used"></div>
	</body>
</html>