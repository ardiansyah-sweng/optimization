<?php

class Random
{
    public static function zeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    public static function ucWeight($position_ranges)
    {
        $simple = mt_rand($position_ranges['simple']['min'] * 100, $position_ranges['simple']['max'] * 100) / 100;
        $average = mt_rand($position_ranges['average']['min'] * 100, $position_ranges['average']['max'] * 100) / 100;
        $complex = mt_rand($position_ranges['complex']['min'] * 100, $position_ranges['complex']['max'] * 100) / 100;
        return [
            'xSimple' => $simple,
            'xAverage' => $average,
            'xComplex' => $complex
        ];
    }
}

$population_size = 10;
$random_seeds = 30;

$position_ranges = [
    'simple' => ['min' => 5, 'max' => 7.49],
    'average' => ['min' => 7.5, 'max' => 12.49],
    'complex' => ['min' => 12.5, 'max' => 15]
];

$positions = [];
for ($i = 0; $i <= $random_seeds - 1; $i++) {
    for ($j = 0; $j <= $population_size - 1; $j++) {
        $positions[] = Random::ucWeight($position_ranges);
    }
}
print_r($positions);

foreach ($positions as $position) {
    $data = array($position['xSimple'], $position['xAverage'], $position['xComplex']);
    $fp = fopen('seeds.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
