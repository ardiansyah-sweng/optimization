<?php
set_time_limit(1000000);
include 'seeds.txt';

class Read
{
    public $index;
    public $column;
    public $filename;

    function __construct($dataset)
    {
        $this->filename = $dataset['filename'];
        $this->index = $dataset['index'];
        $this->column = $dataset['name'];
    }

    public function datasetFile()
    {
        $raw_dataset = file($this->filename);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $this->index[$subkey]) {
                    $data[$key][$this->column[$subkey]] = $data[$key][$subkey];
                    unset($data[$key][$subkey]);
                }
            }
        }
        return $data;
    }
}

class ParticleSwarmOptimizer
{
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
    private $AVOIDED_RANDOM_VALUE = array(0.00, 0.25, 0.50, 0.75, 1.00);
    private $trials;

    function __construct($swarm_size, $C1, $C2, $max_iteration, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $max_counter, $trials)
    {
        $this->swarm_size = $swarm_size;
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
                $xSimple[] = $position['xSimple'];
                $xAverage[] = $position['xAverage'];
                $xComplex[] = $position['xComplex'];
            }
        }
        $ret['xSimple'] = array_sum($xSimple) / count($this->dataset);
        $ret['xAverage'] = array_sum($xAverage) / count($this->dataset);
        $ret['xComplex'] = array_sum($xComplex) / count($this->dataset);
        return $ret;
    }

    function positionUpdating($particles)
    {
        $mean = array_sum(array_column($particles, 'ae')) / count($this->dataset);
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

    function findSolution($project, $initial_populations)
    {
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

                foreach ($initial_populations as $i => $initial_population) {
                    $UCP = $this->size(floatval($initial_population['simple']), $project['simpleUC'], floatval($initial_population['average']), $project['averageUC'], floatval($initial_population['complex']), $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);

                    $esimated_effort = $UCP * $this->productivity_factor;
                    $particles[$iteration + 1][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$iteration + 1][$i]['ucp'] = $UCP;
                    $particles[$iteration + 1][$i]['ae'] = abs($esimated_effort - $project['actualEffort']);
                    $particles[$iteration + 1][$i]['vSimple'] = $this->randomZeroToOne();
                    $particles[$iteration + 1][$i]['vAverage'] = $this->randomZeroToOne();
                    $particles[$iteration + 1][$i]['vComplex'] = $this->randomZeroToOne();
                    $particles[$iteration + 1][$i]['xSimple'] = floatval($initial_population['simple']);
                    $particles[$iteration + 1][$i]['xAverage'] = floatval($initial_population['average']);
                    $particles[$iteration + 1][$i]['xComplex'] = floatval($initial_population['complex']);
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
                }
            } ## End Generate Population

            if ($iteration > 0) {
                $chaos_value[$iteration + 1] = $this->logistic($chaos_value[$iteration]);
                $inertia[$iteration + 1] = $chaos_value[$iteration] * $this->min_inertia + (($this->max_inertia - $this->min_inertia) * $iteration / $this->max_iteration);

                for ($i = 0; $i <= $this->swarm_size - 1; $i++) {
                    $Gbest_simple = $Gbest[$iteration]['xSimple'];
                    $Gbest_average = $Gbest[$iteration]['xAverage'];
                    $Gbest_complex = $Gbest[$iteration]['xComplex'];

                    $Mbest_simple = $Mbests[$iteration]['xSimple'];
                    $Mbest_average = $Mbests[$iteration]['xAverage'];
                    $Mbest_complex = $Mbests[$iteration]['xComplex'];

                    $SPbests_simple = $SPbests[$iteration][$i]['xSimple'];
                    $SPbests_average = $SPbests[$iteration][$i]['xAverage'];
                    $SPbests_complex = $SPbests[$iteration][$i]['xComplex'];

                    $vSimple = $particles[$iteration][$i]['vSimple'];
                    $vAverage = $particles[$iteration][$i]['vAverage'];
                    $vComplex = $particles[$iteration][$i]['vComplex'];

                    $xSimple = $particles[$iteration][$i]['xSimple'];
                    $xAverage = $particles[$iteration][$i]['xAverage'];
                    $xComplex = $particles[$iteration][$i]['xComplex'];

                    $vSimple = $this->velocity($inertia[$iteration], $R1, $R2, $vSimple, $xSimple, $SPbests_simple, $Mbest_simple);
                    $vAverage = $this->velocity($inertia[$iteration], $R1, $R2, $vAverage, $xAverage, $SPbests_average, $Mbest_average);
                    $vComplex = $this->velocity($inertia[$iteration], $R1, $R2, $vComplex, $xComplex, $SPbests_complex, $Mbest_complex);

                    if ($this->positionUpdating($particles[$iteration]) == 'new') {
                        $xSimple = $chaos_value[$iteration] * $xSimple + (1 - $chaos_value[$iteration]) * $vSimple + $Gbest_simple;
                        $xAverage = $chaos_value[$iteration] * $xAverage + (1 - $chaos_value[$iteration]) * $vAverage + $Gbest_average;
                        $xComplex = $chaos_value[$iteration] * $xComplex + (1 - $chaos_value[$iteration]) * $vAverage + $Gbest_complex;
                    }
                    if ($this->positionUpdating($particles[$iteration]) == 'old') {
                        $xSimple = $xSimple + $vSimple;
                        $xAverage = $xAverage + $vAverage;
                        $xComplex = $xComplex + $vComplex;
                    }

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

                    $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;

                    $particles[$iteration + 1][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$iteration + 1][$i]['ucp'] = $UCP;
                    $particles[$iteration + 1][$i]['ae'] = abs($esimated_effort - $project['actualEffort']);
                    $particles[$iteration + 1][$i]['vSimple'] = $vSimple;
                    $particles[$iteration + 1][$i]['vAverage'] = $vAverage;
                    $particles[$iteration + 1][$i]['vComplex'] = $vComplex;
                    $particles[$iteration + 1][$i]['xSimple'] = $xSimple;
                    $particles[$iteration + 1][$i]['xAverage'] = $xAverage;
                    $particles[$iteration + 1][$i]['xComplex'] = $xComplex;
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
        $datasets = [
            'filename' => 'seeds.txt',
            'index' => [0, 1, 2],
            'name' => ['simple', 'average', 'complex']
        ];

        $initial_populations = new Read($datasets);
        $seeds = $initial_populations->datasetFile();
        $end = [];

        for ($i = 0; $i <= $this->trials - 1; $i++) {
            foreach ($this->dataset as $key => $project) {
                if ($key >= 0) {
                    if ($i === 0) {
                        $start = 0;
                    } else {
                        $start = $end[$i - 1] + 1;
                    }
                    $end[$i] = $start + ($this->swarm_size - 1);
                    $initial_populations = Dataset::provide($seeds, $start, $end[$i]);
                    $results[] = $this->findSolution($project, $initial_populations);
                }
            }
            $mae = Arithmatic::mae($results);
            $data = array($mae);
            $fp = fopen('../results/liu.txt', 'a');
            fputcsv($fp, $data);
            fclose($fp);
            $ret[] = $mae;
        }
        return $ret;
    }
}

class Arithmatic
{
    public static function mae($data)
    {
        return array_sum(array_column($data, 'ae')) / count($data);
    }
}

class Dataset
{
    public static function provide($seeds, $start, $end)
    {
        foreach ($seeds as $key => $dataset) {
            if ($key >= $start && $key <= $end) {
                $ret[] = $dataset;
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
        'particle_size' => array(10)
    )
);

foreach ($combinations as $key => $combination) {
    $swarm_size = $combination['particle_size'];
    $C1 = 2;
    $C2 = 2;
    $MAX_ITERATION = 40;
    $max_inertia = 0.9;
    $min_inertia = 0.4;
    $stopping_value = 200;
    $trials = 30;
    $productivity_factor = 20;
    $MAX_COUNTER = 100;

    $optimize = new ParticleSwarmOptimizer($swarm_size, $C1, $C2, $MAX_ITERATION, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $MAX_COUNTER, $trials);
    $optimized = $optimize->finishing();
    print_r($optimized);
}
