<?php
set_time_limit(1000000);
include 'chaotic_interface.php';
include 'raw_data_interface.php';
include 'data_preprocessing.php';

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
        return $A * pow($size, $E) * array_product($effort_multipliers);
    }

    /**
     * Membangkitkan nilai acak dari 0..1
     */
    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
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

    function velocity($parameters)
    {
        return ($parameters['w'] * $parameters['velocity']) + (($parameters['c1'] * $parameters['r1']) * ($parameters['pbest'] - $parameters['position'])) + (($parameters['c2'] * $parameters['r2']) * ($parameters['gbest'] - $parameters['position']));
    }

    function Main($dataset, $max_iter, $swarm_size, $max_counter, $chaotic_type)
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

            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic($chaotic_type, $iterasi);
            $B = $this->randomzeroToOne();
            if ($iterasi == 0) {
                $R1[$iterasi + 1] = $chaos->chaotic(0.7);
                $R2[$iterasi + 1] = $chaos->chaotic(0.7);
                $r[$iterasi + 1] = $chaos->chaotic(0.7);
                $velocity[$iterasi + 1] = $this->randomzeroToOne();

                ##Generate Population
                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    $A = $this->uniformInitialization()[$i]['A'];
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

                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest[$iterasi + 1]);
                $CPbestIndex2 = array_rand($Pbest[$iterasi + 1]);
                $CPbest1 = $Pbest[$iterasi + 1][$CPbestIndex1];
                $CPbest2 = $Pbest[$iterasi + 1][$CPbestIndex2];

                $counter = 0;
                while ($counter < $max_counter) {
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        $CPbestIndex1 = array_rand($Pbest[$iterasi + 1]);
                        $CPbestIndex2 = array_rand($Pbest[$iterasi + 1]);
                        $CPbest1 = $Pbest[$iterasi + 1][$CPbestIndex1];
                        $CPbest2 = $Pbest[$iterasi + 1][$CPbestIndex2];
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
                    foreach ($Pbest[$iterasi + 1] as $key => $val) {
                        if ($CPbest['ae'] < $val['ae']) {
                            $Pbest[$iterasi + 1][$key] = $CPbest;
                        }
                    }
                }
                $SPbest[$iterasi + 1] = $Pbest[$iterasi + 1];
            } // End of iterasi==0

            if ($iterasi > 0) {
                $R1[$iterasi + 1] = $chaos->chaotic($R1[$iterasi]);
                $R2[$iterasi + 1] = $chaos->chaotic($R2[$iterasi]);

                //Inertia weight
                $r[$iterasi + 1] = $chaos->chaotic($r[$iterasi]);
                $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $max_iter);

                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    $parameters = [
                        'w' => $w,
                        'velocity' => $velocity[$iterasi],
                        'c1' => $this->C1,
                        'c2' => $this->C2,
                        'r1' => $R1[$iterasi],
                        'r2' => $R2[$iterasi],
                        'pbest' => $SPbest[$iterasi][$i]['A'],
                        'position' => $particles[$iterasi][$i]['A'],
                        'gbest' => $GBest[$iterasi]['A']
                    ];

                    $velocity[$iterasi + 1] = $this->velocity($parameters);
                    $A = $particles[$iterasi][$i]['A'] + $velocity[$iterasi + 1];

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

                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest[$iterasi + 1]);
                $CPbestIndex2 = array_rand($Pbest[$iterasi + 1]);
                $CPbest1 = $Pbest[$iterasi + 1][$CPbestIndex1];
                $CPbest2 = $Pbest[$iterasi + 1][$CPbestIndex2];

                $counter = 0;
                while ($counter < $max_counter) {
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        $CPbestIndex1 = array_rand($Pbest[$iterasi + 1]);
                        $CPbestIndex2 = array_rand($Pbest[$iterasi + 1]);
                        $CPbest1 = $Pbest[$iterasi + 1][$CPbestIndex1];
                        $CPbest2 = $Pbest[$iterasi + 1][$CPbestIndex2];
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
                    foreach ($SPbest[$iterasi] as $key => $val) {
                        if ($CPbest['ae'] < $val['ae']) {
                            $Pbest[$iterasi + 1][$key] = $CPbest;
                        } else {
                            $Pbest[$iterasi + 1][$key] = $val;
                        }
                    }
                }
                $SPbest[$iterasi + 1] = $Pbest[$iterasi + 1];
            } // End of iterasi > 0

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

    function finishing($project, $max_iter, $swarm_size, $max_counter, $chaotic_type, $max_trial)
    {
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

                for ($i = 0; $i <= $max_trial - 1; $i++) {
                    $results[] = $this->Main($projects, $max_iter, $swarm_size, $max_counter, $chaotic_type);
                }
                $A = array_sum(array_column($results, 'A')) / $this->trials;
                $B = array_sum(array_column($results, 'B')) / $this->trials;
                $results = [];
                $E = $this->scaleEffortExponent($B, $SF);
                $effort = $project['effort'];
                $estimatedEffort = $this->estimating($A, $project['kloc'], $E, $EM);
                $ae = abs($estimatedEffort - $effort);
                $ret[] = array('A' => $A, 'B' => $B, 'E' => $E, 'effort' => $effort, 'estimatedEffort' => $estimatedEffort, 'ae' => $ae);
            }
        }
        return $ret;
    }
}

/**
 * Dataset 71 data point
 * Attribute (7): simple, average, complex, uaw, tcf, ecf, actual effort
 */
$dataset = array(
    array('simpleUC' => 6, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 9, 'tcf' => 0.81, 'ecf' => 0.84, 'actualEffort' => 7970),
    array('simpleUC' => 4, 'averageUC' => 20, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.99, 'ecf' => 0.99, 'actualEffort' => 7962),
    array('simpleUC' => 1, 'averageUC' => 5, 'complexUC' => 20, 'uaw' => 9, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 7935),
    array('simpleUC' => 5, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 7805),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 16, 'uaw' => 8, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 7758),
    array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 14, 'uaw' => 8, 'tcf' => 0.99, 'ecf' => 0.99, 'actualEffort' => 7643),
    array('simpleUC' => 3, 'averageUC' => 18, 'complexUC' => 15, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7532),
    array('simpleUC' => 0, 'averageUC' => 16, 'complexUC' => 12, 'uaw' => 8, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 7451),
    array('simpleUC' => 2, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7449),
    array('simpleUC' => 4, 'averageUC' => 14, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 1.025, 'ecf' => 0.98, 'actualEffort' => 7427),
    array('simpleUC' => 5, 'averageUC' => 16, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7406),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 7365),
    array('simpleUC' => 9, 'averageUC' => 8, 'complexUC' => 19, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 7350),
    array('simpleUC' => 5, 'averageUC' => 8, 'complexUC' => 20, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 7303),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 7252),
    array('simpleUC' => 1, 'averageUC' => 8, 'complexUC' => 16, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7245),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 16, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 7166),
    array('simpleUC' => 5, 'averageUC' => 11, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 7119),
    array('simpleUC' => 3, 'averageUC' => 9, 'complexUC' => 14, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7111),
    array('simpleUC' => 2, 'averageUC' => 14, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 7044),
    array('simpleUC' => 5, 'averageUC' => 14, 'complexUC' => 15, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 7040),
    array('simpleUC' => 3, 'averageUC' => 23, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 7028),
    array('simpleUC' => 1, 'averageUC' => 16, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6942),
    array('simpleUC' => 1, 'averageUC' => 15, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6814),
    array('simpleUC' => 2, 'averageUC' => 19, 'complexUC' => 12, 'uaw' => 9, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6809),
    array('simpleUC' => 2, 'averageUC' => 20, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 6802),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 11, 'uaw' => 12, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6787),
    array('simpleUC' => 1, 'averageUC' => 9, 'complexUC' => 14, 'uaw' => 7, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 6764),
    array('simpleUC' => 4, 'averageUC' => 15, 'complexUC' => 14, 'uaw' => 7, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 6761),
    array('simpleUC' => 0, 'averageUC' => 15, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 6725),
    array('simpleUC' => 1, 'averageUC' => 16, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 6690),
    array('simpleUC' => 0, 'averageUC' => 18, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 6600),
    array('simpleUC' => 0, 'averageUC' => 17, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6474),
    array('simpleUC' => 0, 'averageUC' => 13, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.95, 'ecf' => 0.92, 'actualEffort' => 6433),
    array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6416),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6412),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 6, 'tcf' => 0.9, 'ecf' => 0.94, 'actualEffort' => 6400),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 12, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 6360),
    array('simpleUC' => 0, 'averageUC' => 13, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 6337),
    array('simpleUC' => 1, 'averageUC' => 20, 'complexUC' => 27, 'uaw' => 18, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 6240),
    array('simpleUC' => 1, 'averageUC' => 11, 'complexUC' => 11, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6232),
    array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6173),
    array('simpleUC' => 0, 'averageUC' => 12, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 6160),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 12, 'uaw' => 6, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 6117),
    array('simpleUC' => 2, 'averageUC' => 13, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 6062),
    array('simpleUC' => 1, 'averageUC' => 27, 'complexUC' => 15, 'uaw' => 19, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6051),
    array('simpleUC' => 3, 'averageUC' => 26, 'complexUC' => 15, 'uaw' => 18, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 6048),
    array('simpleUC' => 2, 'averageUC' => 19, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 6035),
    array('simpleUC' => 1, 'averageUC' => 19, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6024),
    array('simpleUC' => 20, 'averageUC' => 25, 'complexUC' => 9, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 6023),
    array('simpleUC' => 5, 'averageUC' => 25, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.118, 'ecf' => 0.995, 'actualEffort' => 5993),
    array('simpleUC' => 4, 'averageUC' => 16, 'complexUC' => 21, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 5985),
    array('simpleUC' => 5, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 5971),
    array('simpleUC' => 5, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.81, 'ecf' => 0.84, 'actualEffort' => 5962),
    array('simpleUC' => 6, 'averageUC' => 16, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5944),
    array('simpleUC' => 5, 'averageUC' => 25, 'complexUC' => 20, 'uaw' => 17, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 5940),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 8, 'uaw' => 6, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5927),
    array('simpleUC' => 3, 'averageUC' => 18, 'complexUC' => 19, 'uaw' => 17, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5885),
    array('simpleUC' => 5, 'averageUC' => 16, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 5882),
    array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 12, 'uaw' => 6, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 5880),
    array('simpleUC' => 3, 'averageUC' => 26, 'complexUC' => 14, 'uaw' => 18, 'tcf' => 0.82, 'ecf' => 0.79, 'actualEffort' => 5880),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.96, 'ecf' => 0.96, 'actualEffort' => 5876),
    array('simpleUC' => 0, 'averageUC' => 3, 'complexUC' => 20, 'uaw' => 6, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5873),
    array('simpleUC' => 3, 'averageUC' => 17, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 5865),
    array('simpleUC' => 2, 'averageUC' => 17, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 5863),
    array('simpleUC' => 3, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5856),
    array('simpleUC' => 2, 'averageUC' => 18, 'complexUC' => 18, 'uaw' => 18, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 5800),
    array('simpleUC' => 1, 'averageUC' => 23, 'complexUC' => 22, 'uaw' => 17, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 5791),
    array('simpleUC' => 5, 'averageUC' => 30, 'complexUC' => 10, 'uaw' => 19, 'tcf' => 0.95, 'ecf' => 0.92, 'actualEffort' => 5782),
    array('simpleUC' => 5, 'averageUC' => 15, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 5778),
    array('simpleUC' => 5, 'averageUC' => 18, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5775)
);

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

$combinations = get_combinations(
    array(
        'chaotic' => array('sinu'),
        'particle_size' => array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100)
        //'chaotic' => array('bernoulli', 'chebyshev', 'circle', 'gauss', 'logistic', 'sine', 'singer', 'sinu'),
    )
);

foreach ($combinations as $key => $combination) {
    for ($i = 0; $i <= 30 - 1; $i++) {
        $MAX_ITER = 40;
        $MAX_TRIAL = 1000;
        $swarm_size = $combination['particle_size'];
        $max_counter = 100000;

        $start = microtime(true);

        $mpucwPSO = new MPUCWPSO($swarm_size, $MAX_TRIAL, $scales);
        $optimized = $mpucwPSO->finishing($dataset, $MAX_ITER, $swarm_size, $max_counter, $combination['chaotic'], $MAX_TRIAL);

        $mae = array_sum(array_column($optimized, 'ae')) / 93;
        echo 'MAE: ' . $mae;
        echo '&nbsp; &nbsp; ';
        print_r($combination);
        echo '<br>';

        $data = array($mae, $combination['particle_size']);
        $fp = fopen('../results/ardi2021.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }
}
