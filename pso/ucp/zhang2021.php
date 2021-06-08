<?php
set_time_limit(100000000);
include 'chaotic_interface.php';

class MPUCWPSO
{
    private $PRODUCTIVITY_FACTOR = 20;
    private $FITNESS_VALUE_BASELINE = array(
        'azzeh1' => 1219.8,
        'azzeh2' => 201.1,
        'azzeh3' => 1564.8,
        'silhavy' => 240.19,
        'karner' => 1820,
        'nassif' => 1712,
        'ardiansyah' => 404.85,
        'polynomial' => 238.11
    );

    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 2;
    private $C2 = 2;
    private $swarm_size;
    private $range_positions;

    function __construct($swarm_size, $range_positions)
    {
        $this->swarm_size = $swarm_size;
        $this->range_positions = $range_positions;
    }


    /**
     * Membangkitkan nilai acak dari 0..1
     */
    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Fungsi AE Minimal
     * Parameter: arrPartikel
     * Return: arrPartikel[indexAEMinimal]
     */
    function minimalAE($arrPartikel)
    {
        foreach ($arrPartikel as $val) {
            $ae[] = $val['ae'];
        }
        return $arrPartikel[array_search(min($ae), $ae)];
    }

    function uniformInitialization()
    {
        $n = $this->swarm_size;
        $X1 = $this->randomUCWeight();
        for ($i = 1; $i <= $n - 1; $i++) {
            $R[$i] = $this->randomUCWeight();
        }
        foreach ($R as $key => $r) {
            $xSimple = $X1['xSimple'] + $r['xSimple'] / $n * ($this->range_positions['max_xSimple'] - $this->range_positions['min_xSimple']);
            $xAverage = $X1['xAverage'] + $r['xAverage'] / $n * ($this->range_positions['max_xAverage'] - $this->range_positions['min_xAverage']);
            $xComplex = $X1['xComplex'] + $r['xComplex'] / $n * ($this->range_positions['max_xComplex'] - $this->range_positions['min_xComplex']);

            if ($xSimple > $this->range_positions['max_xSimple']) {
                $xSimple = $xSimple - ($this->range_positions['max_xSimple'] - $this->range_positions['min_xSimple']);
            }
            if ($xAverage > $this->range_positions['max_xAverage']) {
                $xAverage = $xAverage - ($this->range_positions['max_xAverage'] - $this->range_positions['min_xAverage']);
            }
            if ($xComplex > $this->range_positions['max_xComplex']) {
                $xComplex = $xComplex - ($this->range_positions['max_xComplex'] - $this->range_positions['min_xComplex']);
            }

            if (($key - 1) == 0) {
                $ret[0] = $X1;
            }
            $ret[$key] = ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
        }
        return $ret;
    }

    function randomUCWeight()
    {
        $ret['xSimple'] = mt_rand($this->range_positions['min_xSimple'] * 100, $this->range_positions['max_xSimple'] * 100) / 100;
        $ret['xAverage'] = mt_rand($this->range_positions['min_xAverage'] * 100, $this->range_positions['max_xAverage'] * 100) / 100;
        $ret['xComplex'] = mt_rand($this->range_positions['min_xComplex'] * 100, $this->range_positions['max_xComplex'] * 100) / 100;
        return $ret;
    }

    function size($positions, $projects)
    {
        $ucSimple = $positions['xSimple'] * $projects['simpleUC'];
        $ucAverage = $positions['xAverage'] * $projects['averageUC'];
        $ucComplex = $positions['xComplex'] * $projects['complexUC'];

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $projects['uaw'] + $UUCW;
        return $UUCP * $projects['tcf'] * $projects['ecf'];
    }

    function velocity($parameters)
    {
        return ($parameters['w'] * $parameters['velocity']) + (($parameters['c1'] * $parameters['r1']) * ($parameters['pbest'] - $parameters['position'])) + (($parameters['c2'] * $parameters['r2']) * ($parameters['gbest'] - $parameters['position']));
    }

    function RIW($particle, $particles)
    {
        array_multisort(array_column($particles, 'ae'), SORT_ASC, $particles);
        $rank = array_search($particle['ae'], array_column($particles, 'ae'));
        $b = 1;
        if ($rank <= ($this->swarm_size / 4)) {
            $b = 2 / 3;
        }
        if ($rank >= (3 * $this->swarm_size) / 4) {
            $b = 1.5;
        }
        return $b;
    }

    function Main($dataset, $max_iter, $swarm_size, $max_counter, $chaotic_type)
    {
        //check if there are particles exceeds the lower or upper limit
        $arrLimit = array(
            'xSimple' => array('xSimpleMin' => 5, 'xSimpleMax' => 7.49),
            'xAverage' => array('xAverageMin' => 7.5, 'xAverageMax' => 12.49),
            'xComplex' => array('xComplexMin' => 12.5, 'xComplexMax' => 15)
        );

        ##Masuk Iterasi
        for ($iterasi = 0; $iterasi <= $max_iter; $iterasi++) {
            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic($chaotic_type, $iterasi);
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();

            ##Generate Population
            if ($iterasi === 0) {
                $I[$iterasi + 1] = $iterasi;
                $vSimple[$iterasi + 1] = $this->randomZeroToOne();
                $vAverage[$iterasi + 1] = $this->randomZeroToOne();
                $vComplex[$iterasi + 1] = $this->randomZeroToOne();

                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    $positions = $this->uniformInitialization()[$i];
                    $UCP = $this->size($positions, $dataset);
                    $estimated_effort = $UCP * $this->PRODUCTIVITY_FACTOR;
                    $particles[$iterasi + 1][$i] = [
                        'estimatedEffort' => $estimated_effort,
                        'ae' => abs($estimated_effort - $dataset['actualEffort']),
                        'xSimple' => $positions['xSimple'],
                        'xAverage' => $positions['xAverage'],
                        'xComplex' => $positions['xComplex']
                    ];
                }
                $Pbest[$iterasi + 1] = $particles[$iterasi + 1];
                $GBest[$iterasi + 1] = $this->minimalAE($Pbest[$iterasi + 1]);
            } ## End if iterasi = 0

            if ($iterasi > 0) {
                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $swarm_size - 1; $i++) {

                    //Inertia weight
                    $w_ini = $this->INERTIA_MAX;
                    $w_fin = $this->INERTIA_MIN;

                    $chaos->I = $I[$iterasi];
                    $cosine = $chaos->chaotic($max_iter);
                    $w_cos = ((($w_ini + $w_fin) / 2) + (($w_ini - $w_fin) / 2)) * $cosine;

                    $b = $this->RIW($particles[$iterasi][$i], $particles[$iterasi]);
                    $w = $b * $w_cos;

                    if (($I[$iterasi] <= $max_iter) / 6) {
                        $a = 4 / 3;
                    }
                    if (($max_iter / 6) < $I[$iterasi] && $I[$iterasi] <= (5 * $max_iter) / 6) {
                        $a = 16 / 3;
                    }
                    if ((5 * $max_iter) / 6 < $I[$iterasi] && $I[$iterasi] <= $max_iter) {
                        $a = 2 / 9;
                    }
                    $I[$iterasi + 1] = $I[$iterasi] + $a;

                    $vSimples = [
                        'w' => $w,
                        'velocity' => $vSimple[$iterasi],
                        'c1' => $this->C1, 'c2' => $this->C2,
                        'r1' => $r1, 'r2' => $r2,
                        'pbest' => $Pbest[$iterasi][$i]['xSimple'],
                        'position' => $particles[$iterasi][$i]['xSimple'],
                        'gbest' => $GBest[$iterasi]['xSimple']
                    ];
                    $vAverages = [
                        'w' => $w,
                        'velocity' => $vAverage[$iterasi],
                        'c1' => $this->C1, 'c2' => $this->C2,
                        'r1' => $r1, 'r2' => $r2,
                        'pbest' => $Pbest[$iterasi][$i]['xAverage'],
                        'position' => $particles[$iterasi][$i]['xAverage'],
                        'gbest' => $GBest[$iterasi]['xAverage']
                    ];
                    $vComplexes = [
                        'w' => $w,
                        'velocity' => $vComplex[$iterasi],
                        'c1' => $this->C1, 'c2' => $this->C2,
                        'r1' => $r1, 'r2' => $r2,
                        'pbest' => $Pbest[$iterasi][$i]['xComplex'],
                        'position' => $particles[$iterasi][$i]['xComplex'],
                        'gbest' => $GBest[$iterasi]['xComplex']
                    ];

                    $vSimple[$iterasi + 1] = $this->velocity($vSimples);
                    $xSimple = $particles[$iterasi][$i]['xSimple'] + $vSimple[$iterasi + 1];
                    $vAverage[$iterasi + 1] = $this->velocity($vAverages);
                    $xAverage = $particles[$iterasi][$i]['xAverage'] + $vAverage[$iterasi + 1];
                    $vComplex[$iterasi + 1] = $this->velocity($vComplexes);
                    $xComplex = $particles[$iterasi][$i]['xComplex'] + $vComplex[$iterasi + 1];

                    //exceeding limit
                    if ($xSimple < $arrLimit['xSimple']['xSimpleMin']) {
                        $xSimple = $arrLimit['xSimple']['xSimpleMin'];
                    }
                    if ($xSimple > $arrLimit['xSimple']['xSimpleMax']) {
                        $xSimple = $arrLimit['xSimple']['xSimpleMax'];
                    }
                    if ($xAverage < $arrLimit['xAverage']['xAverageMin']) {
                        $xAverage = $arrLimit['xAverage']['xAverageMin'];
                    }
                    if ($xAverage > $arrLimit['xAverage']['xAverageMax']) {
                        $xAverage = $arrLimit['xAverage']['xAverageMax'];
                    }
                    if ($xComplex < $arrLimit['xComplex']['xComplexMin']) {
                        $xComplex = $arrLimit['xComplex']['xComplexMin'];
                    }
                    if ($xComplex > $arrLimit['xComplex']['xComplexMax']) {
                        $xComplex = $arrLimit['xComplex']['xComplexMax'];
                    }
                    $positions = ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
                    $UCP = $this->size($positions, $dataset);
                    $estEffort = $UCP * $this->PRODUCTIVITY_FACTOR;

                    $particles[$iterasi + 1][$i] = [
                        'estimatedEffort' => $estEffort,
                        'ae' => abs($estEffort - $dataset['actualEffort']),
                        'xSimple' => $positions['xSimple'],
                        'xAverage' => $positions['xAverage'],
                        'xComplex' => $positions['xComplex'],
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
                $GBest[$iterasi + 1] = $this->minimalAE($Pbest[$iterasi + 1]);

                //Fitness value evaluation
                $results = [];
                ## Fitness evaluations
                if ($GBest[$iterasi + 1]['ae'] < $this->FITNESS_VALUE_BASELINE['polynomial']) {
                    return $GBest[$iterasi + 1];
                } else {
                    $results[] = $GBest[$iterasi + 1];
                }
            } // End of iterasi > 0            
        } // End of iterasi
        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    } // End of main()

    function finishing($dataset, $max_iter, $swarm_size, $max_counter, $chaotic_type, $max_trial)
    {
        foreach ($dataset as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $max_trial - 1; $i++) {
                    $results[] = $this->Main($project, $max_iter, $swarm_size, $max_counter, $chaotic_type);
                }
                $xSimple = array_sum(array_column($results, 'xSimple')) / $max_trial;
                $xAverage = array_sum(array_column($results, 'xAverage')) / $max_trial;
                $xComplex = array_sum(array_column($results, 'xComplex')) / $max_trial;
                $positions = ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
                $results = [];

                $UCP = $this->size($positions, $project);

                $estimated_effort = $UCP * $this->PRODUCTIVITY_FACTOR;
                $ae = abs($estimated_effort - floatval($project['actualEffort']));
                $ret[] = array('actualEffort' => $project['actualEffort'], 'estimatedEffort' => $estimated_effort, 'ucp' => $UCP, 'ae' => $ae, 'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex);
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
        //'particle_size' => array(10),
        'chaotic' => array('cosine'),
        'particle_size' => array(100),
        //'chaotic' => array('bernoulli', 'chebyshev', 'circle', 'gauss', 'logistic', 'sine', 'singer', 'sinu'),
    )
);

foreach ($combinations as $key => $combination) {
    for ($i = 0; $i <= 28 - 1; $i++) {
        $MAX_ITER = 40;
        $MAX_TRIAL = 1000;
        $numDataset = count($dataset);
        $swarm_size = $combination['particle_size'];
        $max_counter = 100000;

        $start = microtime(true);
        $range_positions = [
            'min_xSimple' => 5, 
            'max_xSimple' => 7.49, 
            'min_xAverage' => 7.5, 
            'max_xAverage' => 12.49, 
            'min_xComplex' => 12.5, 
            'max_xComplex' => 15
        ];

        $mpucwPSO = new MPUCWPSO($swarm_size, $range_positions);
        $optimized = $mpucwPSO->finishing($dataset, $MAX_ITER, $swarm_size, $max_counter, $combination['chaotic'], $MAX_TRIAL);

        $mae = array_sum(array_column($optimized, 'ae')) / 71;
        echo 'MAE: ' . $mae;
        echo '&nbsp; &nbsp; ';
        print_r($combination);
        echo '<br>';

        $data = array($mae, $combination['particle_size']);
        $fp = fopen('../results/zhang2021.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }
}
