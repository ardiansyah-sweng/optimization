<?php
$file_name = 'sholiq_dataset.txt';
foreach (file($file_name) as $val) {
	$dataset[] = explode(",", $val);
}

//standardization
$min_x = min(array_column($dataset, 0));
$max_x = max(array_column($dataset, 0));
$min_y = min(array_column($dataset, 1));
$max_y = max(array_column($dataset, 1));
foreach ($dataset as $key => $values) {
	$x = floatval($values[0]);
	$y = floatval($values[1]);
	$standards[$key]['x'] = ($x - $min_x) / ($max_x - $min_x);
	$standards[$key]['y'] = (floatval($y) - floatval($min_y)) / (floatval($max_y) - floatval($min_y));
}

$a = [];
$b = [];
$r = 0.02;
for ($i = 0; $i <= 10; $i++) {
	if ($i == 0) {
		$a[$i] = (float) rand() / (float) getrandmax();
		$b[$i] = (float) rand() / (float) getrandmax();
	}
	foreach ($standards as $val) {
		$y_predict = $a[$i] - $b[$i] * $val['x'];
		$SSE = 0.5 * POW(($val['y'] - $y_predict), 2);
		$sum_SSE[] = $SSE;
		$gradient_a = - ($val['y'] - $y_predict);
		$gradient_b = $gradient_a * $val['x'];
		$sum_gradient_a[] = $gradient_a;
		$sum_gradient_b[] = $gradient_b;
		echo $val['x'] . ' | ' . $val['y'] . ' | ' . $y_predict . ' | ' . $SSE . ' | ' . $gradient_a . ' | ' . $gradient_b . '<br>';
	}
	$a[$i + 1] = $a[$i] - $r * array_sum($sum_gradient_a);
	$b[$i + 1] = $b[$i] - $r * array_sum($sum_gradient_b);
	echo 'Sum SSE: ' . array_sum($sum_SSE) . ' Sum Gradient a: ' . array_sum($sum_gradient_a) . ' Sum Gradient b: ' . array_sum($sum_gradient_b);
	echo '<p>';
	$sum_SSE = [];
	$sum_gradient_a = [];
	$sum_gradient_b = [];
}
