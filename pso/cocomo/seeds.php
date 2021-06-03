<?php

class Random
{
    public static function zeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    public static function weight($position_ranges)
    {
        return mt_rand($position_ranges['lower_bound'] * 100, $position_ranges['upper_bound'] * 100) / 100;
    }
}

$population_size = 100;
$random_seeds = 30;

$position_ranges = [
    'lower_bound' => 0.01,
    'upper_bound' => 5
];

$positions = [];
for ($i = 0; $i <= $random_seeds - 1; $i++) {
    for ($j = 0; $j <= $population_size - 1; $j++) {
        $positions[] = Random::weight($position_ranges);
    }
}
print_r($positions);

foreach ($positions as $position) {
    $data = array($position);
    $fp = fopen('seeds.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
