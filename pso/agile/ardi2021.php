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

    function averageWeights($weights, $factor)
    {
        if ($factor == 'friction_factors_weights') {
            foreach ($weights as $weight) {
                $process['ff_team_composition'][] = ($weight[$factor]['ff_team_composition']);
                $process['ff_process'][] = ($weight[$factor]['ff_process']);
                $process['ff_environmental_factors'][] = ($weight[$factor]['ff_environmental_factors']);
                $process['ff_team_dynamics'][] = ($weight[$factor]['ff_team_dynamics']);
            }
        }
        if ($factor == 'dynamic_force_factor_weights') {
            foreach ($weights as $weight) {
                $process['dff_expected_team_change'][] = ($weight[$factor]['dff_expected_team_change']);
                $process['dff_introduction_new_tools'][] = ($weight[$factor]['dff_introduction_new_tools']);
                $process['dff_vendor_defect'][] = ($weight[$factor]['dff_vendor_defect']);
                $process['dff_team_member_responsibility'][] = ($weight[$factor]['dff_team_member_responsibility']);
                $process['dff_personal_issue'][] = ($weight[$factor]['dff_personal_issue']);
                $process['dff_expected_delay'][] = ($weight[$factor]['dff_expected_delay']);
                $process['dff_expected_ambiguity'][] = ($weight[$factor]['dff_expected_ambiguity']);
                $process['dff_expected_change'][] = ($weight[$factor]['dff_expected_change']);
                $process['dff_expected_relocation'][] = ($weight[$factor]['dff_expected_relocation']);
            }
        }
        foreach ($process as $key => $val) {
            $ret[$key] = array_sum($val) / count($weights);
        }
        return $ret;
    }

    function uniformInitialization()
    {
        $n = $this->swarm_size;
        $X1 = mt_rand($this->lower_bound * 100, $this->upper_bound * 100) / 100;
        for ($i = 1; $i <= $n - 1; $i++) {
            $R[$i] = mt_rand($this->lower_bound * 100, $this->upper_bound * 100) / 100;
        }
        foreach ($R as $key => $r) {
            $A = $X1 + $r / $n * ($this->upper_bound - $this->lower_bound);

            if ($A > $this->upper_bound) {
                $A = $A - ($this->upper_bound - $this->lower_bound);
            }

            if (($key - 1) == 0) {
                $ret[0] = ['A' => $X1];
            }
            $ret[$key] = ['A' => $A];
        }
        return $ret;
    }

    function agile($target_projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic('sinu', $generation);

            ## Generate population
            if ($generation === 0) {
                $r1[$generation + 1] = $chaos->chaotic(0.7);
                $r2[$generation + 1] = $chaos->chaotic(0.7);
                $r[$generation + 1] = $chaos->chaotic(0.7);
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
                $GBest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);

                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest[$generation + 1]);
                $CPbestIndex2 = array_rand($Pbest[$generation + 1]);
                $CPbest1 = $Pbest[$generation + 1][$CPbestIndex1];
                $CPbest2 = $Pbest[$generation + 1][$CPbestIndex2];

                $counter = 0;
                while ($counter < $this->max_counter) {
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        $CPbestIndex1 = array_rand($Pbest[$generation + 1]);
                        $CPbestIndex2 = array_rand($Pbest[$generation + 1]);
                        $CPbest1 = $Pbest[$generation + 1][$CPbestIndex1];
                        $CPbest2 = $Pbest[$generation + 1][$CPbestIndex2];
                        $counter = 0;
                    } else {
                        break;
                    }
                }

                if ($CPbestIndex1 != $CPbestIndex2) {
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
                    foreach ($Pbest[$generation + 1] as $key => $val) {
                        if ($CPbest['ae'] < $val['ae']) {
                            $Pbest[$generation + 1][$key] = $CPbest;
                        }
                    }
                }
                $SPbest[$generation + 1] = $Pbest[$generation + 1];
            } ## End if generation = 0

            if ($generation > 0) {
                $r1[$generation + 1] = $chaos->chaotic($r1[$generation]);
                $r2[$generation + 1] = $chaos->chaotic($r2[$generation]);
                //Inertia weight
                $r[$generation + 1] = $chaos->chaotic($r[$generation]);

                $w = $r[$generation] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $generation) / $this->parameters['maximum_generation']);

                foreach ($particles[$generation] as $i => $individu) {
                   // dynamic_force_factor_weights
                    $friction_factor_weights = [
                        'w' => $w,
                        'velocity' => $vel[$generation],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $r1[$generation],
                        'r2' => $r2[$generation],
                        'pbest' => $SPbest[$generation][$i]['friction_factors_weights'],
                        'position' => $individu['friction_factors_weights'],
                        'gbest' => $GBest[$generation]['friction_factors_weights']
                    ];

                    $vel[$generation + 1]['friction_factors_weights'] = $this->velocity($friction_factor_weights);

                    $A = $particles[$iterasi][$i]['A'] + $velocity[$iterasi + 1];

                    ## Rao-1 
                    $friction_factor_weights = $this->updateWeights(
                        $individu['friction_factors_weights'],
                        $r1[$generation],
                        $r2,
                        $candidates['dynamic_force_factor'],
                        $Xbest[$generation]['friction_factors_weights'],
                        $Xworst[$generation]['friction_factors_weights'],
                        'rao-1',
                        'friction_factors'
                    );
                    $dynamic_force_factor_weights = $this->updateWeights(
                        $individu['dynamic_force_factor_weights'],
                        $r1[$generation],
                        $r2,
                        $candidates['dynamic_force_factor'],
                        $Xbest[$generation]['dynamic_force_factor_weights'],
                        $Xworst[$generation]['dynamic_force_factor_weights'],
                        'rao-1',
                        'dynamic_force_factors'
                    );

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

                ## Fitness evaluations
                if ($Xbest[$generation]['ae'] < $this->parameters['fitness']) {
                    return $Xbest[$generation];
                } else {
                    $results[] = $Xbest[$generation];
                }

                $Xbest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
                $Xworst[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            } ## End of if generation > 0
        } ## End of Generation
        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    }

    function processingDataset()
    {
        $data_set = $this->prepareDataset();
        foreach ($data_set as $key => $target_project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->agile($target_project);
                }
                $friction_factor_weights = $this->averageWeights($results, 'friction_factors_weights');
                $dynamic_force_factor_weights = $this->averageWeights($results, 'dynamic_force_factor_weights');
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
        'chaotic' => array('sinu'),
        'particle_size' => array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100),
    )
);

foreach ($combinations as $key => $combination) {
    $particle_size = $combination['particle_size'];
    $maximum_generation = 40;
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

    echo '<p>';
    foreach ($optimized as $key => $result) {
        echo $key . ' ';
        print_r($result['ae']);
        echo '<br>';
        $data = array($result['ae']);
        $fp = fopen('ardi2021.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }
}
