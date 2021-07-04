<?php
set_time_limit(10000000);
include 'chaotic_interface.php';
include 'seeds_spso_cpso.txt';
include 'seeds_class.php';

class SPSO
{
    private $PRODUCTIVITY_FACTOR = 20;
    private $FITNESS_VALUE_BASELINE = array(
        'polynomial' => 238.11
    );

    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    public $swarm_size;
    private $C1 = 2;
    private $C2 = 2;
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
        $ucComplex = floatval($positions['xComplex']) * $projects['complexUC'];

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $projects['uaw'] + $UUCW;
        return $UUCP * $projects['tcf'] * $projects['ecf'];
    }

    function velocity($parameters)
    {
        return ($parameters['w'] * $parameters['velocity']) + (($parameters['c1'] * $parameters['r1']) * (floatval($parameters['pbest']) - floatval($parameters['position']))) + (($parameters['c2'] * $parameters['r2']) * (floatval($parameters['gbest']) - floatval($parameters['position'])));
    }

    function Main($dataset, $max_iter, $swarm_size, $initial_populations)
    {
        //check if there are particles exceeds the lower or upper limit
        $arrLimit = array(
            'xSimple' => array('xSimpleMin' => 5, 'xSimpleMax' => 7.49),
            'xAverage' => array('xAverageMin' => 7.5, 'xAverageMax' => 12.49),
            'xComplex' => array('xComplexMin' => 12.5, 'xComplexMax' => 15)
        );

        ##Masuk Iterasi
        for ($iterasi = 0; $iterasi <= $max_iter; $iterasi++) {
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();
            $w = $this->INERTIA_MIN - ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $max_iter);

            ##Generate Population
            if ($iterasi === 0) {
                $vSimple[$iterasi + 1] = $this->randomZeroToOne();
                $vAverage[$iterasi + 1] = $this->randomZeroToOne();
                $vComplex[$iterasi + 1] = $this->randomZeroToOne();

                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    $UCP = $this->size($initial_populations[$i], $dataset);
                    $estimated_effort = $UCP * $this->PRODUCTIVITY_FACTOR;
                    $particles[$iterasi + 1][$i] = [
                        'estimatedEffort' => $estimated_effort,
                        'ae' => abs($estimated_effort - $dataset['actualEffort']),
                        'xSimple' => $initial_populations[$i]['xSimple'],
                        'xAverage' => $initial_populations[$i]['xAverage'],
                        'xComplex' => $initial_populations[$i]['xComplex']
                    ];
                }
                $Pbest[$iterasi + 1] = $particles[$iterasi + 1];
                $GBest[$iterasi + 1] = $this->minimalAE($Pbest[$iterasi + 1]);
            } ## End if iterasi = 0

            if ($iterasi > 0) {
                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $swarm_size - 1; $i++) {

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
                    $xComplex = floatval($particles[$iterasi][$i]['xComplex']) + $vComplex[$iterasi + 1];

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

    function finishing($dataset, $max_iter, $swarm_size, $max_trial, $numberOfRandomSeeds, $file_name)
    {
        $datasets = [
            'filename' => $file_name,
            'index' => [0, 1, 2],
            'name' => ['xSimple', 'xAverage', 'xComplex']
        ];

        $initial_populations = new Read($datasets);
        $seeds = $initial_populations->datasetFile();
        $ret = [];

        for ($i = 0; $i <= $max_trial - 1; $i++) {
            foreach ($dataset as $key => $project) {
                if ($key >= 0) {
                    $start = 0;
                    $end = $numberOfRandomSeeds - 1;
                    $initial_populations = Dataset::provide($seeds, $start, $end);
                    $results[] = $this->Main($project, $max_iter, $swarm_size, $initial_populations);
                }
            }
            $mae = Arithmatic::mae($results);
            $ret[] = $mae;
            $results = [];
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

$maes = [];
$fileNames = [
    'seeds/spso_cpso_ucpso/seeds0.txt',
    'seeds/spso_cpso_ucpso/seeds1.txt',
    'seeds/spso_cpso_ucpso/seeds2.txt',
    'seeds/spso_cpso_ucpso/seeds3.txt',
    'seeds/spso_cpso_ucpso/seeds4.txt',
    'seeds/spso_cpso_ucpso/seeds5.txt',
    'seeds/spso_cpso_ucpso/seeds6.txt',
    'seeds/spso_cpso_ucpso/seeds7.txt',
    'seeds/spso_cpso_ucpso/seeds8.txt',
    'seeds/spso_cpso_ucpso/seeds9.txt',
    'seeds/spso_cpso_ucpso/seeds10.txt',
    'seeds/spso_cpso_ucpso/seeds11.txt',
    'seeds/spso_cpso_ucpso/seeds12.txt',
    'seeds/spso_cpso_ucpso/seeds13.txt',
    'seeds/spso_cpso_ucpso/seeds14.txt',
    'seeds/spso_cpso_ucpso/seeds15.txt',
    'seeds/spso_cpso_ucpso/seeds16.txt',
    'seeds/spso_cpso_ucpso/seeds17.txt',
    'seeds/spso_cpso_ucpso/seeds18.txt',
    'seeds/spso_cpso_ucpso/seeds19.txt',
    'seeds/spso_cpso_ucpso/seeds20.txt',
    'seeds/spso_cpso_ucpso/seeds21.txt',
    'seeds/spso_cpso_ucpso/seeds22.txt',
    'seeds/spso_cpso_ucpso/seeds23.txt',
    'seeds/spso_cpso_ucpso/seeds24.txt',
    'seeds/spso_cpso_ucpso/seeds25.txt',
    'seeds/spso_cpso_ucpso/seeds26.txt',
    'seeds/spso_cpso_ucpso/seeds27.txt',
    'seeds/spso_cpso_ucpso/seeds28.txt',
    'seeds/spso_cpso_ucpso/seeds29.txt',
];

$max_iter = 60;
$step_size = 6;

for ($iter = $step_size; $iter <= $max_iter; $iter += $step_size) {
    foreach ($fileNames as $file_name) {
        for ($numberOfRandomSeeds = 10; $numberOfRandomSeeds <= 2500; $numberOfRandomSeeds += 10) {
            $combinations = get_combinations(
                array(
                    'particle_size' => array($numberOfRandomSeeds),
                )
            );

            foreach ($combinations as $key => $combination) {
                $MAX_ITER = $iter;
                $MAX_TRIAL = 1;
                $swarm_size = $combination['particle_size'];

                $start = microtime(true);
                $range_positions = [
                    'min_xSimple' => 5,
                    'max_xSimple' => 7.49,
                    'min_xAverage' => 7.5,
                    'max_xAverage' => 12.49,
                    'min_xComplex' => 12.5,
                    'max_xComplex' => 15
                ];

                $mpucwPSO = new SPSO($swarm_size, $range_positions);
                $optimized = $mpucwPSO->finishing($dataset, $MAX_ITER, $swarm_size, $MAX_TRIAL, $numberOfRandomSeeds, $file_name);
                $maes[] = (string)(round($optimized[0]));
            }
        }
        echo '<p>';
        $countAllMAE = array_count_values($maes);
        print_r($countAllMAE);
        echo '<p>';
        $maxStagnantValue = max($countAllMAE);
        $indexMaxStagnantValue = array_search($maxStagnantValue, $countAllMAE);
        echo $maxStagnantValue;
        echo '<br>';
        echo $indexMaxStagnantValue;

        $data = array($iter, $maxStagnantValue, $indexMaxStagnantValue);
        $fp = fopen('../results/psorigin.txt', 'a');
        fputcsv($fp, $data);
        fclose($fp);
        $maes = [];
    }
}
