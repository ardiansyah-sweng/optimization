<?php
set_time_limit(1000000);

class RandomSeedsGenerator
{
    public $sizeOfPopulation;
    public $numberOfRandomSeeds;
    public $rangeOfPositions = [
        'simple' => ['min' => 5, 'max' => 7.49],
        'average' => ['min' => 7.5, 'max' => 12.49],
        'complex' => ['min' => 12.5, 'max' => 15]
    ];

    public static function generateRandomParticle($position_ranges)
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

    public function saveRandomParticle()
    {
        $randomSeeds = [];
        for ($j = 0; $j <= $this->sizeOfPopulation - 1; $j++) {
            $randomSeeds[] = $this->generateRandomParticle($this->rangeOfPositions);
        }
        return $randomSeeds;
    }

    public function writeToTXTFile()
    {
        $randomSeeds = $this->saveRandomParticle();
        foreach ($randomSeeds as $position) {
            $data = array($position['xSimple'], $position['xAverage'], $position['xComplex']);
            $fp = fopen('seeds_master.txt', 'a');
            fputcsv($fp, $data);
            fclose($fp);
        }
    }
}

class RandomSeedsExecutor
{
    public $sizeOfPopulation;
    public $maximumSizeOfPopulation;

    public function main()
    {
        $recordOfParticles = [];
        $collectionOfParticles = [];
        for ($i = 0; $i <= ($this->maximumSizeOfPopulation/$this->sizeOfPopulation)-1; $i++) {
            $randomSeedsGenerator = new RandomSeedsGenerator;
            $randomSeedsGenerator->sizeOfPopulation = $this->sizeOfPopulation;
            $recordOfParticles[] = $randomSeedsGenerator->saveRandomParticle();
            $randomSeedsGenerator->writeToTXTFile();
            $collectionOfParticles[] = $recordOfParticles;
        }
        print_r($recordOfParticles);
        return $collectionOfParticles;
    }
}

$randomSeedsExecutor = new RandomSeedsExecutor;
$sizeOfPopulation = $randomSeedsExecutor->sizeOfPopulation = 10;
$maximumSizeOfPopulation = $randomSeedsExecutor->maximumSizeOfPopulation = 2500;
$collectionOfParticles = $randomSeedsExecutor->main();
