<?php
set_time_limit(1000000);

class RandomSeedsGenerator
{
    public $sizeOfPopulation;
    public $numberOfRandomSeeds;
    public $rangeOfPositions = [
        'lower_bound' => 0.01,
        'upper_bound' => 5
    ];


    public static function zeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    public static function generateRandomParticle($position_ranges)
    {
        return ['A' => mt_rand($position_ranges['lower_bound'] * 100, $position_ranges['upper_bound'] * 100) / 100];
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
            $data = array($position['A']);
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
