<?php
set_time_limit(10000);
include '../chaotic_interface.php';

class PSO
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

    /**
     * Membangkitkan nilai acak dari 0..1
     */
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

    function averageTwoDimensionalArray($data, $column)
    {
        $data_size = count($data);
        return array_sum(array_column($data, $column)) / $data_size;
    }

    function diversity($particles)
    {
        $mean_xSimple = $this->averageTwoDimensionalArray($particles, 'xSimple');
        $mean_xAverage = $this->averageTwoDimensionalArray($particles, 'xAverage');
        $mean_xComplex = $this->averageTwoDimensionalArray($particles, 'xComplex');
        foreach ($particles as $position) {
            $diversity_rate[] = sqrt(pow($position['xSimple'] - $mean_xSimple, 2) + pow($position['xAverage'] - $mean_xAverage, 2) + pow($position['xComplex'] - $mean_xComplex, 2));
        }
        return array_sum($diversity_rate) / count($particles);
    }

    function Main($dataset, $max_iter, $SWARM_SIZE, $limit_percentage, $chaotic_type1, $chaotic_type2)
    {
        ##Generate Population
        for ($i = 0; $i <= $SWARM_SIZE - 1; $i++) {
            $xSimple = $this->randomSimpleUCWeight();
            $xAverage = $this->randomAverageUCWeight();
            $xComplex = $this->randomComplexUCWeight();

            $ucSimple = $xSimple * $dataset['simpleUC'];
            $ucAverage = $xAverage * $dataset['averageUC'];
            $ucComplex = $xComplex * $dataset['complexUC'];

            $UUCW = $ucSimple + $ucAverage + $ucComplex;
            $UUCP = $dataset['uaw'] + $UUCW;
            $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];

            $partikelAwal[$i]['estimatedEffort'] = $UCP * $this->PRODUCTIVITY_FACTOR;
            $partikelAwal[$i]['ae'] = abs($partikelAwal[$i]['estimatedEffort'] - $dataset['actualEffort']);
            $partikelAwal[$i]['xSimple'] = $xSimple;
            $partikelAwal[$i]['xAverage'] = $xAverage;
            $partikelAwal[$i]['xComplex'] = $xComplex;
        }
        //Pada generate partikel, Pbest sama dengan Partikel
        $Pbest = $partikelAwal;
        $GBest = $this->minimalAE($Pbest);
        ##End of generate population

        //check if there are particles exceeds the lower or upper limit
        $arrLimit = array(
            'xSimple' => array('xSimpleMin' => 5, 'xSimpleMax' => 7.49),
            'xAverage' => array('xAverageMin' => 7.5, 'xAverageMax' => 12.49),
            'xComplex' => array('xComplexMin' => 12.5, 'xComplexMax' => 15)
        );

        ##Masuk Iterasi
        $iterasi = 0;
        $diversities = [];
        while ($iterasi <= $max_iter - 1) {

            $chaoticFactory = new ChaoticFactory();
            $chaos1 = $chaoticFactory->initializeChaotic($chaotic_type1, $iterasi);
            $chaos2 = $chaoticFactory->initializeChaotic($chaotic_type1, $iterasi);
            //Inertia weight
            $w = $this->INERTIA_MIN - ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $max_iter);
            if ($iterasi == 0) {
                $R1[$iterasi] = $chaos1->chaotic($this->randomZeroToOne());
                $R2[$iterasi] = $chaos2->chaotic($this->randomZeroToOne());

                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $SWARM_SIZE - 1; $i++) {
                    //update velocity
                    $vSimple = $w * $this->randomZeroToOne() + $this->C1 * $R1[$iterasi] * ($Pbest[$i]['xSimple'] - $partikelAwal[$i]['xSimple']) + $this->C2 * $R2[$iterasi] * ($GBest['xSimple'] - $partikelAwal[$i]['xSimple']);
                    $vAverage = $w * $this->randomZeroToOne() + $this->C1 * $R1[$iterasi] * ($Pbest[$i]['xAverage'] - $partikelAwal[$i]['xAverage']) + $this->C2 * $R2[$iterasi] * ($GBest['xAverage'] - $partikelAwal[$i]['xAverage']);
                    $vComplex = $w * $this->randomZeroToOne() + $this->C1 * $R1[$iterasi] * ($Pbest[$i]['xComplex'] - $partikelAwal[$i]['xComplex']) + $this->C2 * $R2[$iterasi] * ($GBest['xComplex'] - $partikelAwal[$i]['xComplex']);

                    //update positions
                    $xSimple = $partikelAwal[$i]['xSimple'] + $vSimple;
                    $xAverage = $partikelAwal[$i]['xAverage'] + $vAverage;
                    $xComplex = $partikelAwal[$i]['xComplex'] + $vComplex;

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

                    //Use case complexity weight
                    $ucSimple = $xSimple * $dataset['simpleUC'];
                    $ucAverage = $xAverage * $dataset['averageUC'];
                    $ucComplex = $xComplex * $dataset['complexUC'];

                    $UUCW = $ucSimple + $ucAverage + $ucComplex;
                    $UUCP = $UUCW + $dataset['uaw'];
                    $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];

                    $partikel[$iterasi][$i]['estimatedEffort'] = $UCP * $this->PRODUCTIVITY_FACTOR;
                    $partikel[$iterasi][$i]['ae'] = abs($partikel[$iterasi][$i]['estimatedEffort'] - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $xSimple;
                    $partikel[$iterasi][$i]['xAverage'] = $xAverage;
                    $partikel[$iterasi][$i]['xComplex'] = $xComplex;
                    $partikel[$iterasi][$i]['vSimple'] = $vSimple;
                    $partikel[$iterasi][$i]['vAverage'] = $vAverage;
                    $partikel[$iterasi][$i]['vComplex'] = $vComplex;
                } //End of particle loop

                //$diversities[$iterasi] = $this->diversity($partikel[$iterasi]);

                //bandingan Partikel_i(t) dengan PBest_i(t-1)
                foreach ($partikel as $val) {
                    foreach ($val as $key => $x) {
                        if ($Pbest[$key]['ae'] > $x['ae']) {
                            $Pbest[$key] = $x;
                        }
                    }
                }
                $GBest = $this->minimalAE($Pbest);
            } // End of iterasi==0

            if ($iterasi != 0) {
                $R1[$iterasi] = $chaos1->chaotic($R1[$iterasi - 1]);
                $R2[$iterasi] = $chaos2->chaotic($R2[$iterasi - 1]);

                for ($i = 0; $i <= $SWARM_SIZE - 1; $i++) {
                    $vSimple = $partikel[$iterasi - 1][$i]['vSimple'];
                    $vAverage = $partikel[$iterasi - 1][$i]['vAverage'];
                    $vComplex = $partikel[$iterasi - 1][$i]['vComplex'];
                    $xSimple = $partikel[$iterasi - 1][$i]['xSimple'];
                    $xAverage = $partikel[$iterasi - 1][$i]['xAverage'];
                    $xComplex = $partikel[$iterasi - 1][$i]['xComplex'];

                    //Update Velocity
                    $vSimple = ($w * $vSimple) + ($this->C1 * $R1[$iterasi]) * ($Pbest[$i]['xSimple'] - $xSimple) + ($this->C2 * $R2[$iterasi]) * ($GBest['xSimple'] - $xSimple);
                    $vAverage = ($w * $vAverage) + ($this->C1 * $R1[$iterasi]) * ($Pbest[$i]['xAverage'] - $xAverage) + ($this->C2 * $R2[$iterasi]) * ($GBest['xAverage'] - $xAverage);
                    $vComplex = ($w * $vComplex) + ($this->C1 * $R1[$iterasi]) * ($Pbest[$i]['xComplex'] - $xComplex) + ($this->C2 * $R2[$iterasi]) * ($GBest['xComplex'] - $xComplex);

                    //Update positions
                    $xSimple = $xSimple + $vSimple;
                    $xAverage = $xAverage + $vAverage;
                    $xComplex = $xComplex + $vComplex;

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

                    //Use case complexity weight
                    $ucSimple = $xSimple * $dataset['simpleUC'];
                    $ucAverage = $xAverage * $dataset['averageUC'];
                    $ucComplex = $xComplex * $dataset['complexUC'];

                    $UUCW = $ucSimple + $ucAverage + $ucComplex;
                    $UUCP = $UUCW + $dataset['uaw'];
                    $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];

                    $partikel[$iterasi][$i]['estimatedEffort'] = $UCP * $this->PRODUCTIVITY_FACTOR;
                    $partikel[$iterasi][$i]['ae'] = abs($partikel[$iterasi][$i]['estimatedEffort'] - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $xSimple;
                    $partikel[$iterasi][$i]['xAverage'] = $xAverage;
                    $partikel[$iterasi][$i]['xComplex'] = $xComplex;
                    $partikel[$iterasi][$i]['vSimple'] = $vSimple;
                    $partikel[$iterasi][$i]['vAverage'] = $vAverage;
                    $partikel[$iterasi][$i]['vComplex'] = $vComplex;
                }

                $diversities[$iterasi] = $this->diversity($partikel[$iterasi]);

                //bandingan Partikel_i(t) dengan PBest_i(t-1)
                foreach ($partikel as $val) {
                    foreach ($val as $key => $x) {
                        if ($Pbest[$key]['ae'] > $x['ae']) {
                            $Pbest[$key] = $x;
                        }
                    }
                }
                $GBest = $this->minimalAE($Pbest);
            } // End of iterasi > 0

            //Fitness value evaluation
            $results = [];
            ## Fitness evaluations
            if ($GBest['ae'] < $this->FITNESS_VALUE_BASELINE['polynomial']) {
                return $GBest;
            } else {
                $results[] = $GBest;
            }
            $iterasi++;
        } // End of iterasi

        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    } // End of main()

    function size($xSimple, $simpleUC, $xAverage, $averageUC, $xComplex, $complexUC, $uaw, $tcf, $ecf)
    {
        $ucSimple = $xSimple * $simpleUC;
        $ucAverage = $xAverage * $averageUC;
        $ucComplex = $xComplex * $complexUC;

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $uaw + $UUCW;
        return $UUCP * $tcf * $ecf;
    }

    function finishing($dataset, $max_iter, $swarm_size, $max_counter, $chaotic_type1, $chaotic_type2, $max_trial)
    {
        foreach ($dataset as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $max_trial - 1; $i++) {
                    $results[] = $this->Main($project, $max_iter, $swarm_size, $max_counter, $chaotic_type1, $chaotic_type2);
                }
                $xSimple = array_sum(array_column($results, 'xSimple')) / $max_trial;
                $xAverage = array_sum(array_column($results, 'xAverage')) / $max_trial;
                $xComplex = array_sum(array_column($results, 'xComplex')) / $max_trial;
                $results = [];

                $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);

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
        'particle_size' => array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100),
    )
);

foreach ($combinations as $key => $combination) {
    $MAX_ITER = 40;
    $MAX_TRIAL = 30;
    $numDataset = count($dataset);
    $swarm_size = $combination['particle_size'];
    $max_counter = 100000;

    $start = microtime(true);

    $mpucwPSO = new PSO();
    $optimized = $mpucwPSO->finishing($dataset, $MAX_ITER, $swarm_size, $max_counter, 'singer', 'sine', $MAX_TRIAL);

    $mae = array_sum(array_column($optimized, 'ae')) / 71;
    echo 'MAE: ' . $mae;
    echo '&nbsp; &nbsp; ';
    print_r($combination);
    echo '<br>';
    
    $data = array($mae, $combination['particle_size']);
    $fp = fopen('results/tharwat.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}