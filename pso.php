<?php
include 'optimizers/optimizer.php';
include 'estimator_interface.php';
include 'raw_data_interface.php';
include 'data_preprocessing.php';
include 'tes.php';

class Dataset extends DataPreprocessing
{
    function getDataset()
    {
        print_r($this->getListFiles());
    }
}

class ParticleSwarmOptimization extends Optimizer
{
    private $parameters;
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    function randomSimpleUCWeight()
    {
        return mt_rand($this->range_positions['min_xSimple'] * 100, $this->range_positions['max_xSimple'] * 100) / 100;
    }

    function randomAverageUCWeight()
    {
        return mt_rand($this->range_positions['min_xAverage'] * 100, $this->range_positions['max_xAverage'] * 100) / 100;
    }

    function randomComplexUCWeight()
    {
        return mt_rand($this->range_positions['min_xComplex'] * 100, $this->range_positions['max_xComplex'] * 100) / 100;
    }

    function gBest($particles)
    {
        foreach ($particles as $particle) {
            $ae[] = $particle['estimated_effort']['ae'];
        }
        $min_ae = min($ae);
        $index = array_search($min_ae, $ae);
        return $particles[$index];
    }


    function pso()
    {
        $estimator_factory = new EstimatorFactory;
        $rawDataFactory = new RawDataFactory;
        $raw_data = $rawDataFactory->initializeRawData('silhavy');
        $method_names = $rawDataFactory->getEstimationMethods();
        $dataset_columns = $raw_data->rawData();

        $data = new DataPreprocessing($method_names, $dataset_columns);
        $dataset = $data->prepareDataset();
        $population = $this->generatePopulation($this->parameters);
        $estimation = $estimator_factory->initializeOptimizer('ucp');

        $particles = [];
        foreach ($dataset['projects'] as $key => $project) {

            ## Generate population
            $estimateds = $estimation->estimating($population, $project);
            foreach ($estimateds as $key => $estimated) {
                $particles[] = [
                    'estimated_effort' => $estimated,
                    'weights' => $population[$key]
                ];
            }
            ## gBest
            $gBests = $this->gBest($particles);
            $pBests = $particles;

            ## generate new population
            foreach ($particles as $key => $particle){
                echo $key.' ';
                print_r($particle);echo '<br>';
            }

            $particles = [];
        }
    }
}

$range_positions = [
    ['lower' => 5, 'upper' => 7.49],
    ['lower' => 7.5, 'upper' => 12.49],
    ['lower' => 12.5, 'upper' => 15]
];

$parameters = [
    'max_iter' => 40,
    'max_trial' => 1000,
    'swarm_size' => 70,
    'dimensions' => $range_positions
];

$optimized = new ParticleSwarmOptimization($parameters);
$optimized->pso();
