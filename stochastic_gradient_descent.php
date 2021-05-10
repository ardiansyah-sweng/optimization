<?php
set_time_limit(1000000);

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
    $standards[$key]['actual'] = $y;
}

$learning_rate = 0.1;
for ($k = 0; $k <= 2000000-1; $k++){
    for ($j = 0; $j <= count($standards)-1; $j++) {
        //echo 'Objek data ke-'.$j.' Predict Y = ' . $y = $standards[$j]['y'] . ' atau : ' . $standards[$j]['actual'];
        //echo '<br>';
        for ($i = 0; $i <= 20; $i++) {
            $x = $standards[$j]['x'];
            $y = $standards[$j]['y'];
    
            if ($i == 0) {
                $b0[$i] = (float) rand() / (float) getrandmax();
                $b1[$i] = (float) rand() / (float) getrandmax();
            }
            $y_predict = $b0[$i] + $b1[$i] * $x;
            $error = $y_predict - $y;
            $b0[$i + 1] = $b0[$i] - $learning_rate * $error;
            $b1[$i + 1] = $b1[$i] - $learning_rate * $error * $x;
            $percentage = $error * $y;
            $y_predict_real = ($percentage * $standards[$j]['actual']) + $standards[$j]['actual'];
            $absoluteError = abs($y_predict_real - $standards[$j]['actual']);
    
            if ($i == 20) {
                //echo $i . ' | ' . $b0[$i] . ' | ' . $b1[$i] . ' | ' . $y . ' | ' . $y_predict . ' | ' . $y_predict_real . ' | ' . $absoluteError . ' | ' . $error . '<br>';
                $mae[] = $absoluteError;
            }
        }
        $b0 = [];
        $b1 = [];
    }
    $meanAbsoluteError = array_sum($mae);
    $grandMAE[] = $meanAbsoluteError;
    $mae = [];
}
echo min($grandMAE);
$grandMAE = [];

