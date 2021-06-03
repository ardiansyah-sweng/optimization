<?php
set_time_limit(1000000);
include 'chaotic_interface.php';

class Raoptimizer
{
    protected $dataset;
    protected $parameters;
    protected $dataset_name;
    protected $max_counter = 100000;
    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 2;
    private $C2 = 2;
    private $data_size = 21;

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

    function frictionFactorsRandomWeight()
    {
        $ff_team_composition = mt_rand($this->parameters['friction_factors']['ff_team_composition'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;
        $ff_process = mt_rand($this->parameters['friction_factors']['ff_process'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;
        $ff_environmental_factors = mt_rand($this->parameters['friction_factors']['ff_environmental_factors'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;
        $ff_team_dynamics = mt_rand($this->parameters['friction_factors']['ff_environmental_factors'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;

        return [
            'ff_team_composition' => $ff_team_composition,
            'ff_process' => $ff_process,
            'ff_environmental_factors' => $ff_environmental_factors,
            'ff_team_dynamics' => $ff_team_dynamics,
        ];
    }

    function dynamicForceFactorsRandomWeight()
    {
        $dff_expected_team_change = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_team_change'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_introduction_new_tools = mt_rand($this->parameters['dynamic_force_factors']['dff_introduction_new_tools'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_vendor_defect = mt_rand($this->parameters['dynamic_force_factors']['dff_vendor_defect'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_team_member_responsibility = mt_rand($this->parameters['dynamic_force_factors']['dff_team_member_responsibility'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_personal_issue = mt_rand($this->parameters['dynamic_force_factors']['dff_personal_issue'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_delay = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_delay'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_ambiguity = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_ambiguity'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_change = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_change'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_relocation = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_relocation'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;

        return [
            'dff_expected_team_change' => $dff_expected_team_change,
            'dff_introduction_new_tools' => $dff_introduction_new_tools,
            'dff_vendor_defect' => $dff_vendor_defect,
            'dff_team_member_responsibility' => $dff_team_member_responsibility,
            'dff_personal_issue' => $dff_personal_issue,
            'dff_expected_delay' => $dff_expected_delay,
            'dff_expected_ambiguity' => $dff_expected_ambiguity,
            'dff_expected_change' => $dff_expected_change,
            'dff_expected_relocation' => $dff_expected_relocation
        ];
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
            $ret[$key] = ($parameters['w'] * $parameters['velocity']) + (($parameters['c1'] * $parameters['r1']) * ($pbest - $parameters['positions'][$key])) + (($parameters['c2'] * $parameters['r2']) * ($parameters['gbests'][$key] - $parameters['positions'][$key]));
        }
        return $ret;
    }

    function getMbestFF($mbests)
    {
        foreach ($mbests as $key => $mbest) {
            $ff_s['ff_team_composition'][] = $mbest['friction_factors_weights']['ff_team_composition'];
            $ff_s['ff_process'][] = $mbest['friction_factors_weights']['ff_process'];
            $ff_s['ff_environmental_factors'][] = $mbest['friction_factors_weights']['ff_environmental_factors'];
            $ff_s['ff_team_dynamics'][] = $mbest['friction_factors_weights']['ff_team_dynamics'];
        }
        foreach ($ff_s as $key => $mbest) {
            $ret[$key] = array_sum($ff_s[$key]) / $this->data_size;
        }
        return $ret;
    }

    function getMbestDFF($mbests)
    {
        foreach ($mbests as $key => $mbest) {
            $dff_s['dff_expected_team_change'][] = $mbest['dynamic_force_factor_weights']['dff_expected_team_change'];
            $dff_s['dff_introduction_new_tools'][] = $mbest['dynamic_force_factor_weights']['dff_introduction_new_tools'];
            $dff_s['dff_vendor_defect'][] = $mbest['dynamic_force_factor_weights']['dff_vendor_defect'];
            $dff_s['dff_team_member_responsibility'][] = $mbest['dynamic_force_factor_weights']['dff_team_member_responsibility'];
            $dff_s['dff_personal_issue'][] = $mbest['dynamic_force_factor_weights']['dff_personal_issue'];
            $dff_s['dff_expected_delay'][] = $mbest['dynamic_force_factor_weights']['dff_expected_delay'];
            $dff_s['dff_expected_ambiguity'][] = $mbest['dynamic_force_factor_weights']['dff_expected_ambiguity'];
            $dff_s['dff_expected_change'][] = $mbest['dynamic_force_factor_weights']['dff_expected_change'];
            $dff_s['dff_expected_relocation'][] = $mbest['dynamic_force_factor_weights']['dff_expected_relocation'];
        }
        foreach ($dff_s as $key => $mbest) {
            $ret[$key] = array_sum($dff_s[$key]) / $this->data_size;
        }
        return $ret;
    }

    function positionUpdating($particles)
    {
        $mean = array_sum(array_column($particles, 'ae')) / $this->data_size;
        foreach ($particles as $particle) {
            $p = exp($particle['ae']) / $mean;
            if ($p > $this->randomZeroToOne()) {
                return 'new';
            }
            return 'old';
        }
    }

    function Nbest($Gbests, $Pbests)
    {
        $pbest1_index = array_rand($Pbests);
        $pbest2_index = array_rand($Pbests);

        if ($pbest1_index == $pbest2_index) {
            $counter = 0;
            while ($counter < $this->max_counter) {
                if ($pbest1_index == $pbest2_index) {
                    $pbest1_index = array_rand($Pbests);
                    $pbest2_index = array_rand($Pbests);
                    $pbest1 = $Pbests[$pbest1_index];
                    $pbest2 = $Pbests[$pbest2_index];
                    $counter = 0;
                } else {
                    break;
                }
            }
        }
        $pbest1 = $Pbests[$pbest1_index];
        $pbest2 = $Pbests[$pbest2_index];
        return $Gbests['ae'] + ($pbest1['ae'] - $pbest2['ae']);
    }

    function SPbest($Pbests)
    {
        $CPbest1_index = array_rand($Pbests);
        $CPbest2_index = array_rand($Pbests);
        $CPbest1 = $Pbests[$CPbest1_index];
        $CPbest2 = $Pbests[$CPbest2_index];
        $counter = 0;
        while ($counter < $this->max_counter) {
            if ($CPbest1_index == $CPbest2_index) {
                $CPbest1_index = array_rand($Pbests);
                $CPbest2_index = array_rand($Pbests);
                $CPbest1 = $Pbests[$CPbest1_index];
                $CPbest2 = $Pbests[$CPbest2_index];
                $counter = 0;
            } else {
                break;
            }
        }
        if ($CPbest1_index != $CPbest2_index) {
            if ($CPbest1['ae'] < $CPbest2['ae']) {
                $CPbest = $CPbest1;
            }
            if ($CPbest1['ae'] > $CPbest2['ae']) {
                $CPbest = $CPbest2;
            }
            if ($CPbest1['ae'] == $CPbest2['ae']) {
                $CPbest = $CPbest2;
            }
            //compared CPbest with all Pbest_i(t-1)
            foreach ($Pbests as $key => $pbest) {
                if ($CPbest['ae'] < $pbest['ae']) {
                    $SPbests[$key] = $CPbest;
                }
                if ($CPbest['ae'] > $pbest['ae']) {
                    $SPbests[$key] = $pbest;
                }
                if ($CPbest['ae'] == $pbest['ae']) {
                    $SPbests[$key] = $pbest;
                }
            }
        }
        return $SPbests;
    }

    function comparePbests($Pbests, $particles)
    {
        foreach ($Pbests as $key => $pbest) {
            if ($pbest['ae'] > $particles[$key]['ae']) {
                $Pbests[$key] = $particles[$key];
            }
        }
        return $Pbests;
    }

    function agile($target_projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic('logistic', $generation);
            $r1 = $this->randomzeroToOne();
            $r2 = $this->randomzeroToOne();

            ## Generate population
            if ($generation === 0) {

                $r[$generation + 1] = $chaos->chaotic($this->randomzeroToOne());
                $vel[$generation + 1] = $this->randomzeroToOne();

                for ($i = 0; $i <= $this->parameters['particle_size'] - 1; $i++) {
                    $friction_factor_weights = $this->frictionFactorsRandomWeight();
                    $dynamic_force_factor_weights = $this->dynamicForceFactorsRandomWeight();
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
                $SPbests[$generation + 1] = $this->SPbest($particles[$generation + 1]);
                $MbestsFF[$generation + 1] = $this->getMbestFF($Pbest[$generation + 1]);
                $MbestsDFF[$generation + 1] = $this->getMbestDFF($Pbest[$generation + 1]);

                $GBest[$generation + 1] = $this->minimalAE($Pbest[$generation + 1]);
                $Gworsts[$generation + 1] = $this->maximalAE($Pbest[$generation + 1]);
                $Nbest[$generation + 1] = $this->Nbest($GBest[$generation + 1], $Pbest[$generation + 1]);

                if ($Nbest[$generation + 1] < $Gworsts[$generation + 1]['ae']) {
                    $Gworst = $Nbest[$generation + 1];
                } else {
                    $Gworst = $Gworsts[$generation + 1];
                }
            } ## End if generation = 0

            if ($generation > 0) {

                $r[$generation + 1] = $chaos->chaotic($r[$generation]);
                $w = $r[$generation + 1] * $this->INERTIA_MIN + (($this->INERTIA_MAX - $this->INERTIA_MIN) * $generation / $this->parameters['maximum_generation']);

                foreach ($particles[$generation] as $i => $individu) {
                    $vel[$generation + 1] = $particles[$generation][$i];

                    // dynamic_force_factor_weights
                    $friction_factor_weights = [
                        'w' => $w,
                        'velocity' => $vel[$generation],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $r1,
                        'r2' => $r2,
                        'pbests' => $SPbests[$generation][$i]['friction_factors_weights'],
                        'positions' => $individu['friction_factors_weights'],
                        'gbests' => $MbestsFF[$generation]
                    ];

                    $dynamic_force_factor_weights = [
                        'w' => $w,
                        'velocity' => $vel[$generation],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $r1,
                        'r2' => $r2,
                        'pbests' => $SPbests[$generation][$i]['dynamic_force_factor_weights'],
                        'positions' => $individu['dynamic_force_factor_weights'],
                        'gbests' => $MbestsDFF[$generation]
                    ];

                    ## New velocities
                    $vel[$generation + 1]['friction_factors_weights'] = $this->velocityFF($friction_factor_weights);
                    $vel[$generation + 1]['dynamic_force_factor_weights'] = $this->velocityFF($dynamic_force_factor_weights);

                    ## New positions
                    foreach ($vel[$generation + 1]['friction_factors_weights'] as $key => $velocities) {
                        $new_ff_position = $particles[$generation][$i]['friction_factors_weights'][$key] + $velocities;
                        if ($new_ff_position < $this->parameters['friction_factors'][$key]) {
                            $positions_ff[$key] = $this->parameters['friction_factors'][$key];
                        } else if ($new_ff_position > $this->parameters['friction_factors']['max']) {
                            $positions_ff[$key] = $this->parameters['friction_factors']['max'];
                        } else {
                            $positions_ff[$key] = $new_ff_position;
                        }
                    }

                    foreach ($vel[$generation + 1]['dynamic_force_factor_weights'] as $key => $velocities) {
                        $new_dff_position = $particles[$generation][$i]['dynamic_force_factor_weights'][$key] + $velocities;
                        if ($new_dff_position < $this->parameters['dynamic_force_factors'][$key]) {
                            $positions_dff[$key] = $this->parameters['dynamic_force_factors'][$key];
                        } else if ($new_dff_position > $this->parameters['dynamic_force_factors']['max']) {
                            $positions_dff[$key] = $this->parameters['dynamic_force_factors']['max'];
                        } else {
                            $positions_dff[$key] = $new_dff_position;
                        }
                    }

                    $friction_factor = $this->products($positions_ff);
                    $dynamic_force_factor = $this->products($positions_dff);
                    $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                    $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                    $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                    $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

                    $particles[$generation + 1][$i] = [
                        'friction_factors_weights' => $positions_ff,
                        'dynamic_force_factor_weights' => $positions_dff,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                }
                $Pbest[$generation + 1] = $this->comparePbests($Pbest[$generation], $particles[$generation + 1]);
                $SPbests[$generation + 1] = $this->SPbest($Pbest[$generation + 1]);
                $MbestsFF[$generation + 1] = $this->getMbestFF($Pbest[$generation + 1]);
                $MbestsDFF[$generation + 1] = $this->getMbestDFF($Pbest[$generation + 1]);
                $Gbest[$generation + 1] = $this->minimalAE($SPbests[$generation + 1]);
                $Gworsts[$generation + 1] = $this->maximalAE($Pbest[$generation + 1]);
                $Nbests[$generation + 1] = $this->Nbest($Gbest[$generation + 1], $Pbest[$generation + 1]);

                if ($Nbests[$generation + 1] < $Gworsts[$generation + 1]['ae']) {
                    $Gworst = $Nbests[$generation + 1];
                } else {
                    $Gworst = $Gworsts[$generation + 1];
                }
            } ## End of if generation > 0

            ## Fitness evaluations
            if ($Gworst < $this->parameters['fitness']) {
                return $Gworsts[$generation + 1];
            }
            $Gbests[] = $Gworsts[$generation + 1];
        } ## End of Generation

        $best = min(array_column($Gbests, 'ae'));
        $index = array_search($best, array_column($Gbests, 'ae'));
        return $Gbests[$index];
    }

    function processingDataset()
    {
        $data_set = $this->prepareDataset();
        foreach ($data_set as $key => $target_project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->agile($target_project);
                }

                $friction_factor_weights = $this->ffAverageWeights($results);
                $dynamic_force_factor_weights = $this->dffAverageWeights($results);
                $friction_factor = $this->products($friction_factor_weights);
                $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
                $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                $velocity = $this->velocity($target_project['Vi'], $deceleration);
                $estimated_time = $this->estimatedTimeInDays($target_project['effort'], $velocity);
                $absolute_error = $this->absoluteError($estimated_time, $target_project['actual_time']);

                $ret[] = [
                    'friction_factors_weights' => $friction_factor_weights,
                    'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                    'actual_time' => floatval($target_project['actual_time']),
                    'estimated_time' => $estimated_time,
                    'ae' => $absolute_error
                ];
                $results = [];
            }
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

$combinations = get_combinations(
    array(
        // 'chaotic' => array('logistic'),
        'particle_size' => array(30),
    )
);

foreach ($combinations as $key => $combination) {
    for ($maximum_generation = 1; $maximum_generation <= 40; $maximum_generation++) {
        $particle_size = $combination['particle_size'];
        //$maximum_generation = 40;
        $trials = 30;
        $fitness = 0.1;
        $friction_factors = [
            'ff_team_composition' => 0.91,
            'ff_process' => 0.89,
            'ff_environmental_factors' => 0.96,
            'ff_team_dynamics' => 0.85,
            'max' => 1
        ];
        $dynamic_force_factors = [
            'dff_expected_team_change' => 0.91,
            'dff_introduction_new_tools' => 0.96,
            'dff_vendor_defect' => 0.90,
            'dff_team_member_responsibility' => 0.98,
            'dff_personal_issue' => 0.98,
            'dff_expected_delay' => 0.96,
            'dff_expected_ambiguity' => 0.95,
            'dff_expected_change' => 0.97,
            'dff_expected_relocation' => 0.98,
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
        $optimized = $optimize->processingDataset();

        $mae = array_sum(array_column($optimized, 'ae')) / 21;
        echo 'MAE: ' . $mae;
        echo '&nbsp; &nbsp; ';
        print_r($combination);
        echo '<br>';
        $data = array($mae, $maximum_generation);
        $fp = fopen('../results/liu.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }
}
