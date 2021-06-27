<?php
set_time_limit(10000);
include 'chaotic_interface.php';
include 'raw_data_interface.php';
include 'data_preprocessing.php';
include 'seeds_class.php';

class MPUCWPSO
{
    private $FITNESS_VALUE_BASELINE = array(
        'polynomial' => 10
    );

    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 2;
    private $C2 = 2;
    private $swarm_size;
    protected $scales;
    protected $lower_bound = 0.01;
    protected $upper_bound = 5;
    protected $dataset = 'cocomo_nasa93.txt';

    function __construct($swarm_size, $trials, $scales)
    {
        $this->swarm_size = $swarm_size;
        $this->trials = $trials;
        $this->scales = $scales;
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        $column_indexes = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25];
        $columns = ['prec', 'flex', 'resl', 'team', 'pmat', 'rely', 'data', 'cplx', 'ruse', 'docu', 'time', 'stor', 'pvol', 'acap', 'pcap', 'pcon', 'apex', 'plex', 'ltex', 'tool', 'site', 'sced', 'kloc', 'effort', 'defects', 'months'];
        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $column_indexes[$subkey]) {
                    $data[$key][$columns[$subkey]] = $data[$key][$subkey];
                    unset($data[$key][$subkey]);
                }
            }
        }
        return $data;
    }

    function scaleEffortExponent($B, $scale_factors)
    {
        return $B + 0.01 * array_sum($scale_factors);
    }

    function estimating($A, $size, $E, $effort_multipliers)
    {
        return floatval($A) * pow($size, $E) * array_product($effort_multipliers);
    }

    /**
     * Membangkitkan nilai acak dari 0..1
     */
    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    function velocity($parameters)
    {
        return ($parameters['w'] * $parameters['velocity']) + (($parameters['c1'] * $parameters['r1']) * (floatval($parameters['pbest']) - floatval($parameters['position']))) + (($parameters['c2'] * $parameters['r2']) * (floatval($parameters['gbest']) - floatval($parameters['position'])));
    }

    function Main($dataset, $max_iter, $swarm_size, $max_counter, $chaotic_type, $initial_populations)
    {
        $SF['prec'] = $dataset['prec'];
        $SF['flex'] = $dataset['flex'];
        $SF['resl'] = $dataset['resl'];
        $SF['team'] = $dataset['team'];
        $SF['pmat'] = $dataset['pmat'];
        $EM['rely'] = $dataset['rely'];
        $EM['data'] = $dataset['data'];
        $EM['cplx'] = $dataset['cplx'];
        $EM['ruse'] = $dataset['ruse'];
        $EM['docu'] = $dataset['docu'];
        $EM['time'] = $dataset['time'];
        $EM['stor'] = $dataset['stor'];
        $EM['pvol'] = $dataset['pvol'];
        $EM['acap'] = $dataset['acap'];
        $EM['pcap'] = $dataset['pcap'];
        $EM['pcon'] = $dataset['pcon'];
        $EM['apex'] = $dataset['apex'];
        $EM['plex'] = $dataset['plex'];
        $EM['ltex'] = $dataset['ltex'];
        $EM['tool'] = $dataset['tool'];
        $EM['site'] = $dataset['site'];
        $EM['sced'] = $dataset['sced'];

        ##Masuk Iterasi
        for ($iterasi = 0; $iterasi <= $max_iter; $iterasi++) {
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();
            $B = $this->randomzeroToOne();
            $w = $this->INERTIA_MIN - ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $max_iter);

            if ($iterasi == 0) {
                $velocity[$iterasi + 1] = $this->randomzeroToOne();

                ##Generate Population
                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    $A = $initial_populations[$i]['A'];
                    $E = $this->scaleEffortExponent($B, $SF);
                    $estimated_effort = $this->estimating($A, $dataset['kloc'], $E, $EM);
                    $particles[$iterasi + 1][$i] = [
                        'A' => $A,
                        'B' => $B,
                        'E' => $E,
                        'EM' => array_sum($EM), 'SF' => array_sum($SF),
                        'size' => $dataset['kloc'], 'effort' => $dataset['effort'],
                        'estimatedEffort' => $estimated_effort,
                        'ae' => abs($estimated_effort - $dataset['effort'])
                    ];
                }
                $Pbest[$iterasi + 1] = $particles[$iterasi + 1];

                $min = min(array_column($Pbest[$iterasi + 1], 'ae'));
                $index = array_search($min, $Pbest[$iterasi + 1]);
                $GBest[$iterasi + 1] = $Pbest[$iterasi + 1][$index];
            } ## End if iterasi==0

            if ($iterasi > 0) {
                //Inertia weight


                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    $parameters = [
                        'w' => $w,
                        'velocity' => $velocity[$iterasi],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $r1,
                        'r2' => $r2,
                        'pbest' => $Pbest[$iterasi][$i]['A'],
                        'position' => $particles[$iterasi][$i]['A'],
                        'gbest' => $GBest[$iterasi]['A']
                    ];

                    $velocity[$iterasi + 1] = $this->velocity($parameters);
                    $A = floatval($particles[$iterasi][$i]['A']) + $velocity[$iterasi + 1];

                    //exceeding limit
                    if ($A < $this->lower_bound) {
                        $A = $this->lower_bound;
                    }
                    if ($A > $this->upper_bound) {
                        $A = $this->upper_bound;
                    }

                    $E = $this->scaleEffortExponent($B, $SF);

                    $estimated_effort = $this->estimating($A, $dataset['kloc'], $E, $EM);
                    $particles[$iterasi + 1][$i] = [
                        'A' => $A,
                        'B' => $B,
                        'E' => $E,
                        'EM' => array_sum($EM), 'SF' => array_sum($SF),
                        'size' => $dataset['kloc'], 'effort' => $dataset['effort'],
                        'estimatedEffort' => $estimated_effort,
                        'ae' => abs($estimated_effort - $dataset['effort'])
                    ];
                }

                //bandingan Partikel_i(t) dengan PBest_i(t-1)
                foreach ($particles[$iterasi + 1] as $key => $val) {
                    if ($val['ae'] < $Pbest[$iterasi][$key]['ae']) {
                        $Pbest[$iterasi + 1][$key] = $val;
                    } else {
                        $Pbest[$iterasi + 1][$key] = $Pbest[$iterasi][$key];
                    }
                }
                $min_ae = min(array_column($Pbest[$iterasi + 1], 'ae'));
                $index = array_search($min_ae, $Pbest[$iterasi + 1]);

                $GBest[$iterasi + 1] = $Pbest[$iterasi + 1][$index];
            } ## End if iterasi > 0

            //Fitness value evaluation
            $results = [];
            ## Fitness evaluations
            if ($GBest[$iterasi + 1]['ae'] < $this->FITNESS_VALUE_BASELINE['polynomial']) {
                return $GBest[$iterasi + 1];
            } else {
                $results[] = $GBest[$iterasi + 1];
            }
        } // End of iterasi

        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    } // End of main()

    function finishing($max_iter, $swarm_size, $max_counter, $chaotic_type, $max_trial, $numberOfRandomSeeds, $file_name)
    {
        $datasets = [
            'filename' => $file_name,
            'index' => 0,
            'name' => 'A'
        ];

        $initial_populations = new Read($datasets);
        $seeds = $initial_populations->datasetFile();
        $ret = [];

        for ($i = 0; $i <= $max_trial - 1; $i++) {
            foreach ($this->prepareDataset() as $key => $project) {
                if ($key >= 0) {
                    $projects['prec'] = $this->scales['prec'][$project['prec']];
                    $projects['flex'] = $this->scales['flex'][$project['flex']];
                    $projects['resl'] = $this->scales['resl'][$project['resl']];
                    $projects['team'] = $this->scales['team'][$project['team']];
                    $projects['pmat'] = $this->scales['pmat'][$project['pmat']];
                    $projects['rely'] = $this->scales['rely'][$project['rely']];
                    $projects['data'] = $this->scales['data'][$project['data']];
                    $projects['cplx'] = $this->scales['cplx'][$project['cplx']];
                    $projects['ruse'] = $this->scales['ruse'][$project['ruse']];
                    $projects['docu'] = $this->scales['docu'][$project['docu']];
                    $projects['time'] = $this->scales['time'][$project['time']];
                    $projects['stor'] = $this->scales['stor'][$project['stor']];
                    $projects['pvol'] = $this->scales['pvol'][$project['pvol']];
                    $projects['acap'] = $this->scales['acap'][$project['acap']];
                    $projects['pcap'] = $this->scales['pcap'][$project['pcap']];
                    $projects['pcon'] = $this->scales['pcon'][$project['pcon']];
                    $projects['apex'] = $this->scales['apex'][$project['apex']];
                    $projects['plex'] = $this->scales['plex'][$project['plex']];
                    $projects['ltex'] = $this->scales['ltex'][$project['ltex']];
                    $projects['tool'] = $this->scales['tool'][$project['tool']];
                    $projects['site'] = $this->scales['site'][$project['site']];
                    $projects['sced'] = $this->scales['sced'][$project['sced']];
                    $projects['kloc'] = $project['kloc'];
                    $projects['effort'] = $project['effort'];
                    $projects['defects'] = $project['defects'];
                    $projects['months'] = $project['months'];

                    $SF['prec'] = $projects['prec'];
                    $SF['flex'] = $projects['flex'];
                    $SF['resl'] = $projects['resl'];
                    $SF['team'] = $projects['team'];
                    $SF['pmat'] = $projects['pmat'];
                    $EM['rely'] = $projects['rely'];
                    $EM['data'] = $projects['data'];
                    $EM['cplx'] = $projects['cplx'];
                    $EM['ruse'] = $projects['ruse'];
                    $EM['docu'] = $projects['docu'];
                    $EM['time'] = $projects['time'];
                    $EM['stor'] = $projects['stor'];
                    $EM['pvol'] = $projects['pvol'];
                    $EM['acap'] = $projects['acap'];
                    $EM['pcap'] = $projects['pcap'];
                    $EM['pcon'] = $projects['pcon'];
                    $EM['apex'] = $projects['apex'];
                    $EM['plex'] = $projects['plex'];
                    $EM['ltex'] = $projects['ltex'];
                    $EM['tool'] = $projects['tool'];
                    $EM['site'] = $projects['site'];
                    $EM['sced'] = $projects['sced'];

                    $start = 0;
                    $end = $numberOfRandomSeeds - 1;
                    $initial_populations = Dataset::provide($seeds, $start, $end);
                    $results[] = $this->Main($projects, $max_iter, $swarm_size, $max_counter, $chaotic_type, $initial_populations);
                }
            }
            $mae = Arithmatic::mae($results);
            $ret[] = $mae;
            $results = [];
        }
        return $ret;
    }
}

$scales = array(
    "prec" => array("vl" => 6.2, "l" => 4.96, "n" => 3.72, "h" => 2.48, "vh" => 1.24, "eh" => 0),
    "flex" => array("vl" => 5.07, "l" => 4.05, "n" => 3.04, "h" => 2.03, "vh" => 1.01, "eh" => 0),
    "resl" => array("vl" => 7.07, "l" => 5.65, "n" => 4.24, "h" => 2.83, "vh" => 1.41, "eh" => 0),
    "team" => array("vl" => 5.48, "l" => 4.38, "n" => 3.29, "h" => 2.19, "vh" => 1.10, "eh" => 0),
    "pmat" => array("vl" => 7.80, "l" => 6.24, "n" => 4.68, "h" => 3.12, "vh" => 1.56, "eh" => 0),
    "rely" => array("vl" => 0.82, "l" => 0.92, "n" => 1.00, "h" => 1.10, "vh" => 1.26, "eh" => ''),
    "data" => array("vl" => '', "l" => 0.90, "n" => 1.00, "h" => 1.14, "vh" => 1.28, "eh" => ''),
    "cplx" => array("vl" => 0.73, "l" => 0.87, "n" => 1.00, "h" => 1.17, "vh" => 1.34, "eh" => 1.74),
    "ruse" => array("vl" => '', "l" => 0.95, "n" => 1.00, "h" => 1.07, "vh" => 1.15, "eh" => 1.24),
    "docu" => array("vl" => 0.81, "l" => 0.91, "n" => 1.00, "h" => 1.11, "vh" => 1.23, "eh" => ''),
    "time" => array("vl" => '', "l" => '', "n" => 1.00, "h" => 1.11, "vh" => 1.29, "eh" => 1.63),
    "stor" => array("vl" => '', "l" => '', "n" => 1.00, "h" => 1.05, "vh" => 1.17, "eh" => 1.46),
    "pvol" => array("vl" => '', "l" => 0.87, "n" => 1.00, "h" => 1.15, "vh" => 1.30, "eh" => ''),
    "acap" => array("vl" => 1.42, "l" => 1.19, "n" => 1.00, "h" => 0.85, "vh" => 0.71, "eh" => ''),
    "pcap" => array("vl" => 1.34, "l" => 1.15, "n" => 1.00, "h" => 0.88, "vh" => 0.76, "eh" => ''),
    "pcon" => array("vl" => 1.29, "l" => 1.12, "n" => 1.00, "h" => 0.90, "vh" => 0.81, "eh" => ''),
    "apex" => array("vl" => 1.22, "l" => 1.10, "n" => 1.00, "h" => 0.88, "vh" => 0.81, "eh" => ''),
    "plex" => array("vl" => 1.19, "l" => 1.09, "n" => 1.00, "h" => 0.91, "vh" => 0.85, "eh" => ''),
    "ltex" => array("vl" => 1.20, "l" => 1.09, "n" => 1.00, "h" => 0.91, "vh" => 0.84, "eh" => ''),
    "tool" => array("vl" => 1.17, "l" => 1.09, "n" => 1.00, "h" => 0.90, "vh" => 0.78, "eh" => ''),
    "site" => array("vl" => 1.22, "l" => 1.09, "n" => 1.00, "h" => 0.93, "vh" => 0.86, "eh" => 0.80),
    "sced" => array("vl" => 1.43, "l" => 1.14, "n" => 1.00, "h" => 1.00, "vh" => 1.00, "eh" => '')
);

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
    'filenames/spso/seeds_spso0.txt',
    'filenames/spso/seeds_spso1.txt',
    'filenames/spso/seeds_spso2.txt',
    'filenames/spso/seeds_spso3.txt',
    'filenames/spso/seeds_spso4.txt',
    'filenames/spso/seeds_spso5.txt',
    'filenames/spso/seeds_spso6.txt',
    'filenames/spso/seeds_spso7.txt',
    'filenames/spso/seeds_spso8.txt',
    'filenames/spso/seeds_spso9.txt',
    'filenames/spso/seeds_spso10.txt',
    'filenames/spso/seeds_spso11.txt',
    'filenames/spso/seeds_spso12.txt',
    'filenames/spso/seeds_spso13.txt',
    'filenames/spso/seeds_spso14.txt',
    'filenames/spso/seeds_spso15.txt',
    'filenames/spso/seeds_spso16.txt',
    'filenames/spso/seeds_spso17.txt',
    'filenames/spso/seeds_spso18.txt',
    'filenames/spso/seeds_spso19.txt',
    'filenames/spso/seeds_spso20.txt',
    'filenames/spso/seeds_spso21.txt',
    'filenames/spso/seeds_spso22.txt',
    'filenames/spso/seeds_spso23.txt',
    'filenames/spso/seeds_spso24.txt',
    'filenames/spso/seeds_spso25.txt',
    'filenames/spso/seeds_spso26.txt',
    'filenames/spso/seeds_spso27.txt',
    'filenames/spso/seeds_spso28.txt',
    'filenames/spso/seeds_spso29.txt'
];

foreach ($fileNames as $file_name) {
    for ($numberOfRandomSeeds = 10; $numberOfRandomSeeds <= 2500; $numberOfRandomSeeds += 10) {
        $combinations = get_combinations(
            array(
                'chaotic' => array('sinu'),
                'particle_size' => array($numberOfRandomSeeds),
            )
        );

        foreach ($combinations as $key => $combination) {
            $MAX_ITER = 40;
            $MAX_TRIAL = 1;
            $swarm_size = $combination['particle_size'];
            $max_counter = 100000;

            $start = microtime(true);

            $mpucwPSO = new MPUCWPSO($swarm_size, $MAX_TRIAL, $scales);
            $optimized = $mpucwPSO->finishing($MAX_ITER, $swarm_size, $max_counter, $combination['chaotic'], $MAX_TRIAL, $numberOfRandomSeeds, $file_name);
            $maes[] = (string)(round($optimized[0]));
        }
    }
    echo '<p>';
    $countAllMAE = array_count_values($maes);
    // print_r($countAllMAE);
    echo '<p>';
    $maxStagnantValue = max($countAllMAE);
    $indexMaxStagnantValue = array_search($maxStagnantValue, $countAllMAE);
    echo $maxStagnantValue;
    echo '<br>';
    echo $indexMaxStagnantValue;

    $data = array($maxStagnantValue, $indexMaxStagnantValue);
    $fp = fopen('../results/psorigin.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
    $maes = [];
}
