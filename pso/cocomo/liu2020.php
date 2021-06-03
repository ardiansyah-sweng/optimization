<?php
set_time_limit(1000000);
include 'raw_data_interface.php';
include 'data_preprocessing.php';

class ParticleSwarmOptimizer
{
    protected $scales;
    protected $swarm_size;
    protected $C1;
    protected $C2;
    protected $max_iteration;
    protected $max_inertia;
    protected $min_inertia;
    protected $stopping_value;
    protected $dataset;
    protected $productivity_factor;
    protected $MAX_COUNTER;
    protected $lower_bound = 0.01;
    protected $upper_bound = 5;
    private $AVOIDED_RANDOM_VALUE = array(0.00, 0.25, 0.50, 0.75, 1.00);
    private $trials;
    private $data_size = 93;

    function __construct($swarm_size, $C1, $C2, $max_iteration, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $max_counter, $trials, $scales)
    {
        $this->swarm_size = $swarm_size;
        $this->scales = $scales;
        $this->C1 = $C1;
        $this->C2 = $C2;
        $this->max_iteration = $max_iteration;
        $this->max_inertia = $max_inertia;
        $this->min_inertia = $min_inertia;
        $this->stopping_value = $stopping_value;
        $this->dataset = $dataset;
        $this->productivity_factor = $productivity_factor;
        $this->MAX_COUNTER = $max_counter;
        $this->trials = $trials;
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

    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Generate random Simple Use Case Complexity weight parameter
     * Min = 5,     xMinSimple = 4.5
     * Max = 7.49   xMaxSimple = 8.239
     */
    function randomSimpleUCWeight()
    {
        $MIN = 5;
        $MAX = 7.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Average Use Case Complexity weight parameter
     * Min = 7.5    xMinAverage = 6.75
     * Max = 12.49  xMaxAverage = 13.739
     */
    function randomAverageUCWeight()
    {
        $MIN = 7.5;
        $MAX = 12.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Complex Use Case Complexity weight parameter
     * Min = 12.5   xMinComplex = 11.25
     * Max = 15     xMaxComplex = 16.5
     */
    function randomComplexUCWeight()
    {
        $MIN = 12.5;
        $MAX = 15;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
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

    function size($xSimple, $simpleUC, $xAverage, $averageUC, $xComplex, $complexUC, $uaw, $tcf, $ecf)
    {
        $ucSimple = $xSimple * $simpleUC;
        $ucAverage = $xAverage * $averageUC;
        $ucComplex = $xComplex * $complexUC;

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $uaw + $UUCW;
        return $UUCP * $tcf * $ecf;
    }

    function velocity($inertia, $R1, $R2, $velocity, $position, $Pbest, $Gbest)
    {
        return $inertia * $velocity + ($this->C1 * $R1) * ($Pbest - $position) + ($this->C2 * $R2) * ($Gbest - $position);
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

    function SPbest($Pbests)
    {
        $CPbest1_index = array_rand($Pbests);
        $CPbest2_index = array_rand($Pbests);
        $CPbest1 = $Pbests[$CPbest1_index];
        $CPbest2 = $Pbests[$CPbest2_index];
        $counter = 0;
        while ($counter < $this->MAX_COUNTER) {
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

    function randomNumber($r0)
    {
        $i = 0;
        while ($i < count($this->AVOIDED_RANDOM_VALUE)) {
            if ($this->AVOIDED_RANDOM_VALUE[$i] == $r0) {
                $r0 = number_format($this->randomZeroToOne(), 2);
                $i = 0;
            }
            if ($this->AVOIDED_RANDOM_VALUE[$i] != $r0) {
                $i++;
            }
        }
        return $r0;
    }

    function logistic($chaos_value)
    {
        $r0 = $this->randomNumber(number_format($chaos_value, 2));
        return (4 * $r0) * (1 - $r0);
    }

    function getMbest($mbests)
    {
        foreach ($mbests as $mbest) {
            foreach ($mbest as $position) {
                $A[] = $position['A'];
            }
        }
        $ret['A'] = array_sum($A) / $this->data_size;
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
            while ($counter < $this->MAX_COUNTER) {
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

    function findSolution($project)
    {
        $SF['prec'] = $project['prec'];
        $SF['flex'] = $project['flex'];
        $SF['resl'] = $project['resl'];
        $SF['team'] = $project['team'];
        $SF['pmat'] = $project['pmat'];
        $EM['rely'] = $project['rely'];
        $EM['data'] = $project['data'];
        $EM['cplx'] = $project['cplx'];
        $EM['ruse'] = $project['ruse'];
        $EM['docu'] = $project['docu'];
        $EM['time'] = $project['time'];
        $EM['stor'] = $project['stor'];
        $EM['pvol'] = $project['pvol'];
        $EM['acap'] = $project['acap'];
        $EM['pcap'] = $project['pcap'];
        $EM['pcon'] = $project['pcon'];
        $EM['apex'] = $project['apex'];
        $EM['plex'] = $project['plex'];
        $EM['ltex'] = $project['ltex'];
        $EM['tool'] = $project['tool'];
        $EM['site'] = $project['site'];
        $EM['sced'] = $project['sced'];

        $arrLimit = array(
            'xSimple' => array('xSimpleMin' => 5, 'xSimpleMax' => 7.49),
            'xAverage' => array('xAverageMin' => 7.5, 'xAverageMax' => 12.49),
            'xComplex' => array('xComplexMin' => 12.5, 'xComplexMax' => 15)
        );

        for ($iteration = 0; $iteration <= $this->max_iteration - 1; $iteration++) {
            $R1  = $this->randomZeroToOne();
            $R2  = $this->randomZeroToOne();

            ## Generate population
            if ($iteration === 0) {
                $chaos_initial = $this->logistic($this->randomZeroToOne());
                $chaos_value[$iteration + 1] = $chaos_initial;
                $inertia[$iteration + 1] = $chaos_initial * $this->min_inertia + (($this->max_inertia - $this->min_inertia) * $iteration / $this->max_iteration);

                for ($i = 0; $i <= $this->swarm_size - 1; $i++) {
                    $A = mt_rand($this->lower_bound * 100, $this->upper_bound * 100) / 100;
                    $B[$iteration + 1] = $this->logistic($this->randomzeroToOne());
                    $E = $this->scaleEffortExponent($B[$iteration + 1], $SF); ## chaotic

                    $estimated_effort = $this->estimating($A, $project['kloc'], $E, $EM);

                    $particles[$iteration + 1][$i]['A'] = $A;
                    $particles[$iteration + 1][$i]['B'] = $B[$iteration + 1]; ## chaotic
                    $particles[$iteration + 1][$i]['E'] = $E;
                    $particles[$iteration + 1][$i]['EM'] = array_sum($EM);
                    $particles[$iteration + 1][$i]['SF'] = array_sum($SF);
                    $particles[$iteration + 1][$i]['size'] = $project['kloc'];
                    $particles[$iteration + 1][$i]['effort'] = $project['effort'];
                    $particles[$iteration + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$iteration + 1][$i]['ae'] = abs($estimated_effort - $project['effort']);
                }

                $Pbests[$iteration + 1] = $particles[$iteration + 1];
                $SPbests[$iteration + 1] = $this->SPbest($particles[$iteration + 1]);

                $mbests[] = $particles[$iteration + 1];
                $Mbests[$iteration + 1] = $this->getMbest($mbests);
                $Gbest[$iteration + 1] = $this->minimalAE($Pbests[$iteration + 1]);
                $Gworsts[$iteration + 1] = $this->maximalAE($Pbests[$iteration + 1]);
                $Nbests[$iteration + 1] = $this->Nbest($Gbest[$iteration + 1], $Pbests[$iteration + 1]);

                if ($Nbests[$iteration + 1] < $Gworsts[$iteration + 1]['ae']) {
                    $Gworst = $Nbests[$iteration + 1];
                } else {
                    $Gworst = $Gworsts[$iteration + 1];
                }
            } ## End Generate Population

            if ($iteration > 0) {
                $chaos_value[$iteration + 1] = $this->logistic($chaos_value[$iteration]);
                $inertia[$iteration + 1] = $chaos_value[$iteration] * $this->min_inertia + (($this->max_inertia - $this->min_inertia) * $iteration / $this->max_iteration);
                $B[$iteration + 1] = $this->logistic($B[$iteration]);

                for ($i = 0; $i <= $this->swarm_size - 1; $i++) {
                    $GbestA = $Gbest[$iteration]['A'];
                    $MbestA = $Mbests[$iteration]['A'];
                    $SPbestsA = $SPbests[$iteration][$i]['A'];
                    $velocity = $particles[$iteration][$i]['A'];
                    $A = $particles[$iteration][$i]['A'];

                    $velocity = $this->velocity($inertia[$iteration], $R1, $R2, $velocity, $A, $SPbestsA, $MbestA);

                    if ($this->positionUpdating($particles[$iteration]) == 'new') {
                        $A = $chaos_value[$iteration] * $A + (1 - $chaos_value[$iteration]) * $velocity + $GbestA;
                    }
                    if ($this->positionUpdating($particles[$iteration]) == 'old') {
                        $A = $A + $velocity;
                    }

                    if ($A < $this->lower_bound) {
                        $A = $this->lower_bound;
                    }
                    if ($A > $this->upper_bound) {
                        $A = $this->upper_bound;
                    }

                    $E = $this->scaleEffortExponent($B[$iteration + 1], $SF); ## chaotic

                    $estimated_effort = $this->estimating($A, $project['kloc'], $E, $EM);

                    $particles[$iteration + 1][$i]['A'] = $A;
                    $particles[$iteration + 1][$i]['B'] = $B[$iteration + 1]; ## chaotic
                    $particles[$iteration + 1][$i]['E'] = $E;
                    $particles[$iteration + 1][$i]['EM'] = array_sum($EM);
                    $particles[$iteration + 1][$i]['SF'] = array_sum($SF);
                    $particles[$iteration + 1][$i]['size'] = $project['kloc'];
                    $particles[$iteration + 1][$i]['effort'] = $project['effort'];
                    $particles[$iteration + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$iteration + 1][$i]['ae'] = abs($estimated_effort - $project['effort']);
                }
                $Pbests[$iteration + 1] = $this->comparePbests($Pbests[$iteration], $particles[$iteration + 1]);
                $SPbests[$iteration + 1] = $this->SPbest($Pbests[$iteration + 1]);
                $mbests[] = $Pbests[$iteration + 1];
                $Mbests[$iteration + 1] = $this->getMbest($mbests);
                $Gbest[$iteration + 1] = $this->minimalAE($SPbests[$iteration + 1]);
                $Gworsts[$iteration + 1] = $this->maximalAE($Pbests[$iteration + 1]);
                $Nbests[$iteration + 1] = $this->Nbest($Gbest[$iteration + 1], $Pbests[$iteration + 1]);

                if ($Nbests[$iteration + 1] < $Gworsts[$iteration + 1]['ae']) {
                    $Gworst = $Nbests[$iteration + 1];
                } else {
                    $Gworst = $Gworsts[$iteration + 1];
                }
            } ## End IF iteration > 0
            if ($Gworst < $this->stopping_value) {
                return $Gworsts[$iteration + 1];
            }
            $Gbests[] = $Gworsts[$iteration + 1];
        } ## End of iteration

        $minimal_AE = min(array_column($Gbests, 'ae'));
        $index_minimal_AE = array_search($minimal_AE, array_column($Gbests, 'ae'));
        return $Gbests[$index_minimal_AE];
    } ## End of findSolution()

    function finishing()
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

                for ($i = 0; $i <= $this->trials - 1; $i++) {
                    $results[] = $this->findSolution($projects);
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
        'particle_size' => array(10)
    )
);

foreach ($combinations as $key => $combination) {
    for ($MAX_ITERATION = 1; $MAX_ITERATION <= 40; $MAX_ITERATION++) {
        $dataset = 'cocomo_nasa93.txt';
        $swarm_size = $combination['particle_size'];
        $C1 = 2;
        $C2 = 2;
        //$MAX_ITERATION = 40;
        $max_inertia = 0.9;
        $min_inertia = 0.4;
        $stopping_value = 10;
        $trials = 30;
        $productivity_factor = 20;
        $MAX_COUNTER = 100;

        $optimize = new ParticleSwarmOptimizer($swarm_size, $C1, $C2, $MAX_ITERATION, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $MAX_COUNTER, $trials, $scales);
        $optimized = $optimize->finishing();

        $mae = array_sum(array_column($optimized, 'ae')) / 93;
        echo 'MAE: ' . $mae;
        echo '&nbsp; &nbsp; ';
        print_r($combination);
        echo '<br>';

        $data = array($mae, $MAX_ITERATION);
        $fp = fopen('../results/liu.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
        $absolute_errors = [];
    }
}
