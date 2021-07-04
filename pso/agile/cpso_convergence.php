<?php
set_time_limit(100000000);
include 'chaotic_interface.php';
include 'seeds_class.php';

class Raoptimizer
{
    protected $dataset;
    protected $parameters;
    protected $dataset_name;
    protected $max_counter = 100000;
    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 1.5;
    private $C2 = 1.5;

    function __construct($dataset, $parameters, $dataset_name)
    {
        $this->dataset = $dataset;
        $this->parameters = $parameters;
        $this->dataset_name = $dataset_name;
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset[$this->dataset_name]['file_name']);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $this->dataset[$this->dataset_name]['column_indexes'][$subkey]) {
                    $data[$key][$this->dataset[$this->dataset_name]['columns'][$subkey]] = $data[$key][$subkey];
                    unset($data[$key][$subkey]);
                }
            }
        }
        return $data;
    }

    function minimalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(min($ae), $ae)];
    }

    function maximalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(max($ae), $ae)];
    }

    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    function estimatedTimeInDays($effort, $velocity)
    {
        return $effort / $velocity;
    }

    function absoluteError($estimated, $actual)
    {
        return abs($estimated - floatval($actual));
    }

    function products($weights)
    {
        return array_product($weights);
    }

    function deceleration($friction_factor, $dynamic_force_factor)
    {
        return $friction_factor * $dynamic_force_factor;
    }

    function velocity($Vi, $deceleration)
    {
        return pow($Vi, $deceleration);
    }

    function ffAverageWeights($weights)
    {
        if (!is_array($weights)) {
            return new Exception("Weights is not array");
        }
        foreach ($weights as $key => $weight) {
            $process[$key] = $weight['friction_factors_weights'];
        }

        foreach ($process as $val) {
            $ret['ff_team_composition'] = array_sum(array_column($process, 'ff_team_composition')) / $this->parameters['trials'];
            $ret['ff_process'] = array_sum(array_column($process, 'ff_process')) / $this->parameters['trials'];
            $ret['ff_environmental_factors'] = array_sum(array_column($process, 'ff_environmental_factors')) / $this->parameters['trials'];
            $ret['ff_team_dynamics'] = array_sum(array_column($process, 'ff_team_dynamics')) / $this->parameters['trials'];
        }
        return $ret;
    }

    function dffAverageWeights($weights)
    {
        if (!is_array($weights)) {
            return new Exception("Weights is not array");
        }

        foreach ($weights as $key => $weight) {
            $process[$key] = $weight['dynamic_force_factor_weights'];
        }

        foreach ($process as $key => $val) {
            $ret['dff_expected_team_change'] = array_sum(array_column($process, 'dff_expected_team_change')) / $this->parameters['trials'];
            $ret['dff_introduction_new_tools'] = array_sum(array_column($process, 'dff_introduction_new_tools')) / $this->parameters['trials'];
            $ret['dff_vendor_defect'] = array_sum(array_column($process, 'dff_vendor_defect')) / $this->parameters['trials'];
            $ret['dff_team_member_responsibility'] = array_sum(array_column($process, 'dff_team_member_responsibility')) / $this->parameters['trials'];
            $ret['dff_personal_issue'] = array_sum(array_column($process, 'dff_personal_issue')) / $this->parameters['trials'];
            $ret['dff_expected_delay'] = array_sum(array_column($process, 'dff_expected_delay')) / $this->parameters['trials'];
            $ret['dff_expected_ambiguity'] = array_sum(array_column($process, 'dff_expected_ambiguity')) / $this->parameters['trials'];
            $ret['dff_expected_change'] = array_sum(array_column($process, 'dff_expected_change')) / $this->parameters['trials'];
            $ret['dff_expected_relocation'] = array_sum(array_column($process, 'dff_expected_relocation')) / $this->parameters['trials'];
        }
        return $ret;
    }

    function velocityFF($parameters)
    {
        if (!is_array($parameters['pbests'])) {
            return new Exception("Pbest is not an array");
        }
        if (!is_float($parameters['w'])) {
            return new Exception("w is not a float");
        }
        if (!is_float($parameters['velocity'])) {
            return new Exception("velocity is not a float");
        }
        if (!is_array($parameters['positions'])) {
            return new Exception("position particle is not an array");
        }
        if (!is_array($parameters['gbests'])) {
            return new Exception("gbest position particle is not an array");
        }
        foreach ($parameters['pbests'] as $key => $pbest) {
            $ret[$key] = ($parameters['w'] * $parameters['velocity']) + (($parameters['c1'] * $parameters['r1']) * (floatval($pbest) - floatval($parameters['positions'][$key]))) + (($parameters['c2'] * $parameters['r2']) * (floatval($parameters['gbests'][$key]) - floatval($parameters['positions'][$key])));
        }
        return $ret;
    }

    function split($seeds)
    {
        return [
            'ff' => [
                $seeds['ff_team_composition'],
                $seeds['ff_process'],
                $seeds['ff_environmental_factors'],
                $seeds['ff_team_dynamics']
            ],
            'dff' => [
                $seeds['dff_expected_team_change'],
                $seeds['dff_introduction_new_tools'],
                $seeds['dff_vendor_defect'],
                $seeds['dff_team_member_responsibility'],
                $seeds['dff_personal_issue'],
                $seeds['dff_expected_delay'],
                $seeds['dff_expected_ambiguity'],
                $seeds['dff_expected_change'],
                $seeds['dff_expected_relocation']
            ]
        ];
    }

    function agile($target_projects, $initial_populations)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $chaoticFactory = new ChaoticFactory();
            $chaos1 = $chaoticFactory->initializeChaotic('singer', $generation);
            $chaos2 = $chaoticFactory->initializeChaotic('sine', $generation);
            $w = $this->INERTIA_MIN - ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $generation) / $this->parameters['maximum_generation']);

            ## Generate population
            if ($generation === 0) {
                $r1[$generation + 1] = $chaos1->chaotic(0.7);
                $r2[$generation + 1] = $chaos2->chaotic(0.7);

                $vel[$generation + 1] = $this->randomzeroToOne();

                for ($i = 0; $i <= $this->parameters['particle_size'] - 1; $i++) {
                    $friction_factor_weights = $this->split($initial_populations[$i])['ff'];
                    $dynamic_force_factor_weights = $this->split($initial_populations[$i])['dff'];
                    $friction_factor = $this->products($friction_factor_weights);
                    $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
                    $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                    $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                    $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                    $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

                    $particles[$generation + 1][$i] = [
                        'friction_factors_weights' => $friction_factor_weights,
                        'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                }
                $Pbest[$generation + 1] = $particles[$generation + 1];
                $GBest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
            } ## End if generation = 0

            if ($generation > 0) {
                $r1[$generation + 1] = $chaos1->chaotic($r1[$generation]);
                $r2[$generation + 1] = $chaos2->chaotic($r2[$generation]);

                foreach ($particles[$generation] as $i => $individu) {

                    // dynamic_force_factor_weights
                    $friction_factor_weights = [
                        'w' => $w,
                        'velocity' => $vel[$generation],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $r1[$generation],
                        'r2' => $r2[$generation],
                        'pbests' => $Pbest[$generation][$i]['friction_factors_weights'],
                        'positions' => $individu['friction_factors_weights'],
                        'gbests' => $GBest[$generation]['friction_factors_weights']
                    ];

                    $dynamic_force_factor_weights = [
                        'w' => $w,
                        'velocity' => $vel[$generation],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $r1[$generation],
                        'r2' => $r2[$generation],
                        'pbests' => $Pbest[$generation][$i]['dynamic_force_factor_weights'],
                        'positions' => $individu['dynamic_force_factor_weights'],
                        'gbests' => $GBest[$generation]['dynamic_force_factor_weights']
                    ];

                    ## New velocities
                    $vel[$generation + 1]['friction_factors_weights'] = $this->velocityFF($friction_factor_weights);

                    $vel[$generation + 1]['dynamic_force_factor_weights'] = $this->velocityFF($dynamic_force_factor_weights);

                    ## New positions
                    foreach ($vel[$generation + 1]['friction_factors_weights'] as $key => $velocities) {
                        $positions_ff[$key] = $particles[$generation][$i]['friction_factors_weights'][$key] + $velocities;
                    }
                    foreach ($vel[$generation + 1]['dynamic_force_factor_weights'] as $key => $velocities) {
                        $positions_dff[$key] = floatval($particles[$generation][$i]['dynamic_force_factor_weights'][$key]) + $velocities;
                    }

                    foreach ($positions_ff as $key => $ff) {
                        if ($ff < $this->parameters['friction_factors'][$key]) {
                            $ff_weights[$key] = $this->parameters['friction_factors'][$key];
                        }
                        if ($ff > $this->parameters['friction_factors']['max']) {
                            $ff_weights[$key] = $this->parameters['friction_factors']['max'];
                        }
                        if ($ff > $this->parameters['friction_factors'][$key] && $ff < $this->parameters['friction_factors']['max']) {
                            $ff_weights[$key] = $ff;
                        }
                    }

                    foreach ($positions_dff as $key => $dff) {
                        if ($dff < $this->parameters['dynamic_force_factors'][$key]) {
                            $dff_weights[$key] = $this->parameters['dynamic_force_factors'][$key];
                        }
                        if ($dff > $this->parameters['dynamic_force_factors']['max']) {
                            $dff_weights[$key] = $this->parameters['dynamic_force_factors']['max'];
                        }
                        if ($dff > $this->parameters['dynamic_force_factors'][$key] && $ff < $this->parameters['dynamic_force_factors']['max']) {
                            $dff_weights[$key] = $dff;
                        }
                    }

                    $friction_factor = $this->products($ff_weights);
                    $dynamic_force_factor = $this->products($dff_weights);
                    $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                    $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                    $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                    $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

                    $friction_factor_weights = [$ff_weights];
                    $dynamic_force_factor_weights = [$dff_weights];

                    $particles[$generation + 1][$i] = [
                        'friction_factors_weights' => $friction_factor_weights,
                        'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                }
                $Pbest[$generation + 1] = $particles[$generation + 1];
                $GBest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
            } ## End of if generation > 0

            ## Fitness evaluations
            if ($GBest[$generation + 1]['ae'] < $this->parameters['fitness']) {
                return $GBest[$generation + 1];
            } else {
                $results[] = $GBest[$generation + 1];
            }
        } ## End of Generation

        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    }

    function processingDataset($numberOfRandomSeeds, $file_name)
    {
        $datasets = [
            'filename' => $file_name,
            'index' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            'name' => ['ff_team_composition', 'ff_process', 'ff_environmental_factors', 'ff_team_dynamics', 'dff_expected_team_change', 'dff_introduction_new_tools', 'dff_vendor_defect', 'dff_team_member_responsibility', 'dff_personal_issue', 'dff_expected_delay', 'dff_expected_ambiguity', 'dff_expected_change', 'dff_expected_relocation']
        ];
        $initial_populations = new Read($datasets);
        $seeds = $initial_populations->datasetFile();
        $end = [];
        $ret = [];
        $data_set = $this->prepareDataset();
        for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
            foreach ($data_set as $key => $target_project) {
                if ($key >= 0) {
                    $start = 0;
                    $end = $numberOfRandomSeeds - 1;
                    $initial_populations = Dataset::provide($seeds, $start, $end);
                    $results[] = $this->agile($target_project, $initial_populations);
                }
            }
            $mae = Arithmatic::mae($results);
            $ret[] = $mae;
        }
        return $ret;
    }
} ## End of Raoptimizer

$ziauddin_column_indexes = [0, 1, 2, 3, 4, 5, 6];
$ziauddin_columns = ['effort', 'Vi', 'D', 'V', 'sprint_size', 'work_days', 'actual_time'];

$dataset_name = 'ziauddin';
$dataset = [
    'ziauddin' => [
        'file_name' => 'agile_ziauddin.txt',
        'column_indexes' => $ziauddin_column_indexes,
        'columns' => $ziauddin_columns
    ]
];
function get_combinations($arrays)
{
    $result = array(array());
    foreach ($arrays as $property => $property_values) {
        $tmp = array();
        foreach ($result as $result_item) {
            foreach ($property_values as $property_value) {
                $tmp[] = array_merge($result_item, array($property => $property_value));
            }
        }
        $result = $tmp;
    }
    return $result;
}

$maes = [];
$fileNames = [
    'seeds_spso_cpso0.txt',
    'seeds_spso_cpso1.txt',
    'seeds_spso_cpso2.txt',
    'seeds_spso_cpso3.txt',
    'seeds_spso_cpso4.txt',
    'seeds_spso_cpso5.txt',
    'seeds_spso_cpso6.txt',
    'seeds_spso_cpso7.txt',
    'seeds_spso_cpso8.txt',
    'seeds_spso_cpso9.txt',
    'seeds_spso_cpso10.txt',
    'seeds_spso_cpso11.txt',
    'seeds_spso_cpso12.txt',
    'seeds_spso_cpso13.txt',
    'seeds_spso_cpso14.txt',
    'seeds_spso_cpso15.txt',
    'seeds_spso_cpso16.txt',
    'seeds_spso_cpso17.txt',
    'seeds_spso_cpso18.txt',
    'seeds_spso_cpso19.txt',
    'seeds_spso_cpso20.txt',
    'seeds_spso_cpso21.txt',
    'seeds_spso_cpso22.txt',
    'seeds_spso_cpso23.txt',
    'seeds_spso_cpso24.txt',
    'seeds_spso_cpso25.txt',
    'seeds_spso_cpso26.txt',
    'seeds_spso_cpso27.txt',
    'seeds_spso_cpso28.txt',
    'seeds_spso_cpso29.txt',
];

$max_iter = 61;
$step_size = 4;

for ($iter = 1; $iter <= $max_iter; $iter += $step_size) {
    foreach ($fileNames as $file_name) {
        for ($numberOfRandomSeeds = 10; $numberOfRandomSeeds <= 2500; $numberOfRandomSeeds += 10) {
            $combinations = get_combinations(
                array(
                    'chaotic' => array('sinu'),
                    'particle_size' => array($numberOfRandomSeeds),
                )
            );

            foreach ($combinations as $key => $combination) {
                $particle_size = $combination['particle_size'];
                $maximum_generation = $iter;
                $trials = 1;
                $fitness = 0.1;
                $friction_factors = [
                    0.91,
                    0.89,
                    0.96,
                    0.85,
                    'max' => 1
                ];
                $dynamic_force_factors = [
                    0.91,
                    0.96,
                    0.90,
                    0.98,
                    0.98,
                    0.96,
                    0.95,
                    0.97,
                    0.98,
                    'max' => 1
                ];
                $parameters = [
                    'particle_size' => $particle_size,
                    'maximum_generation' => $maximum_generation,
                    'trials' => $trials,
                    'fitness' => $fitness,
                    'friction_factors' => $friction_factors,
                    'dynamic_force_factors' => $dynamic_force_factors
                ];

                $optimize = new Raoptimizer($dataset, $parameters, $dataset_name);
                $optimized = $optimize->processingDataset($numberOfRandomSeeds, $file_name);
                $maes[] = (string)$optimized[0];
            }
        }


        $countAllMAE = array_count_values($maes);
        print_r($countAllMAE);
        echo '<p>';
        $maxStagnantValue = max($countAllMAE);
        $indexMaxStagnantValue = array_search($maxStagnantValue, $countAllMAE);
        echo $maxStagnantValue;
        echo '<br>';
        echo $indexMaxStagnantValue;

        $data = array($iter, $maxStagnantValue, $indexMaxStagnantValue);
        $fp = fopen('../results/tharwat.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
        $maes = [];
    }
}