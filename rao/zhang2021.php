<?php
set_time_limit(1000000);
include '../chaotic_interface.php';

class Raoptimizer
{
    protected $dataset_name;
    protected $parameters;
    protected $productivity_factor;
    protected $range_positions;

    function __construct($dataset_name, $parameters, $productivity_factor, $range_positions)
    {
        $this->dataset_name = $dataset_name;
        $this->parameters = $parameters;
        $this->productivity_factor = $productivity_factor;
        $this->range_positions = $range_positions;
    }

    function singer($value)
    {
        return 1.07 * ((7.86 * $value) - (23.31 * POW($value, 2)) + (28.75 * POW($value, 3)) - (13.302875 * POW($value, 4)));
    }

    function sinu($chaos_value)
    {
        return (2.3 * POW($chaos_value, 2)) * sin(pi() * $chaos_value);
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset_name);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        $column_indexes = [0, 1, 2, 3, 4, 5, 6];
        $columns = ['simpleUC', 'averageUC', 'complexUC', 'uaw', 'tcf', 'ecf', 'actualEffort'];
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

    function candidating($particles, $individu)
    {
        $ae_candidate_xSimple1 = $particles[$key_xSimple1 = array_rand($particles)]['ae'];
        $ae_candidate_xSimple2 = $particles[$key_xSimple2 = array_rand($particles)]['ae'];
        $ae_candidate_xAverage1 = $particles[$key_xAverage1 = array_rand($particles)]['ae'];
        $ae_candidate_xAverage2 = $particles[$key_xAverage2 = array_rand($particles)]['ae'];
        $ae_candidate_xComplex1 = $particles[$key_xComplex1 = array_rand($particles)]['ae'];
        $ae_candidate_xComplex2 = $particles[$key_xComplex2 = array_rand($particles)]['ae'];

        if ($individu['ae'] > $ae_candidate_xSimple1) {
            $xSimple1 = $particles[$key_xSimple1]['xSimple'];
        }
        if ($individu['ae'] < $ae_candidate_xSimple1 || $individu['ae'] == $ae_candidate_xSimple1) {
            $xSimple1 = $individu['xSimple'];
        }
        if ($individu['ae'] > $ae_candidate_xSimple2) {
            $xSimple2 = $particles[$key_xSimple2]['xSimple'];
        }
        if ($individu['ae'] < $ae_candidate_xSimple2 || $individu['ae'] == $ae_candidate_xSimple2) {
            $xSimple2 = $individu['xSimple'];
        }

        if ($individu['ae'] > $ae_candidate_xAverage1) {
            $xAverage1 = $particles[$key_xAverage1]['xAverage'];
        }
        if ($individu['ae'] < $ae_candidate_xAverage1 || $individu['ae'] == $ae_candidate_xAverage1) {
            $xAverage1 = $individu['xAverage'];
        }
        if ($individu['ae'] > $ae_candidate_xAverage2) {
            $xAverage2 = $particles[$key_xAverage2]['xAverage'];
        }
        if ($individu['ae'] < $ae_candidate_xAverage2 || $individu['ae'] == $ae_candidate_xAverage2) {
            $xAverage2 = $individu['xAverage'];
        }

        if ($individu['ae'] > $ae_candidate_xComplex1) {
            $xComplex1 = $particles[$key_xComplex1]['xComplex'];
        }
        if ($individu['ae'] < $ae_candidate_xComplex1 || $individu['ae'] == $ae_candidate_xComplex1) {
            $xComplex1 = $individu['xComplex'];
        }
        if ($individu['ae'] > $ae_candidate_xComplex2) {
            $xComplex2 = $particles[$key_xComplex2]['xComplex'];
        }
        if ($individu['ae'] < $ae_candidate_xComplex2 || $individu['ae'] == $ae_candidate_xComplex2) {
            $xComplex2 = $individu['xComplex'];
        }

        return ['xSimple1' => $xSimple1, 'xSimple2' => $xSimple2, 'xAverage1' => $xAverage1, 'xAverage2' => $xAverage2, 'xComplex1' => $xComplex1, 'xComplex2' => $xComplex2];
    }

    function uniformInitialization()
    {
        $D = 3;
        $n = $this->parameters['particle_size'];
        $X1 = $this->randomUCWeight();
        for ($i = 1; $i <= $n - 1; $i++) {
            $R[$i] = $this->randomUCWeight();
        }
        foreach ($R as $key => $r){
            $xSimple = $X1['xSimple'] + $r['xSimple'] / $n * ($this->range_positions['max_xSimple']- $this->range_positions['min_xSimple']);
            $xAverage = $X1['xAverage'] + $r['xAverage'] / $n * ($this->range_positions['max_xAverage']- $this->range_positions['min_xAverage']);
            $xComplex = $X1['xComplex'] + $r['xComplex'] / $n * ($this->range_positions['max_xComplex']- $this->range_positions['min_xComplex']);

            if ($xSimple > $this->range_positions['max_xSimple']){
                $xSimple = $xSimple - ($this->range_positions['max_xSimple']- $this->range_positions['min_xSimple']);
            }
            if ($xAverage > $this->range_positions['max_xAverage']){
                $xAverage = $xAverage - ($this->range_positions['max_xAverage']- $this->range_positions['min_xAverage']);
            }
            if ($xComplex > $this->range_positions['max_xComplex']){
                $xComplex = $xComplex - ($this->range_positions['max_xComplex']- $this->range_positions['min_xComplex']);
            }
            
            if (($key-1) == 0){
                $ret[0] = $X1;
            }
            $ret[$key] = ['xSimple'=>$xSimple, 'xAverage'=>$xAverage, 'xComplex'=>$xComplex];
        }
        return $ret;
    }

    function UCP($projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic('singer', $generation);
            // $r1 = $this->randomZeroToOne();
            // $r2 = $this->randomZeroToOne();

            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->parameters['particle_size']-1; $i++) {
                    $r1[$generation + 1] = $chaos->chaotic($this->randomZeroToOne());
                    $r2[$generation + 1] = $chaos->chaotic($this->randomZeroToOne());

                    // $positions1 = $this->randomUCWeight();
                    // print_r($positions1);echo '<p>';
                    $positions = $this->uniformInitialization()[$i];
                    // print_r($positions);
                    // dd($positions);

                    $UCP = $this->size($positions, $projects);
                    $estimated_effort = $UCP * $this->productivity_factor;
                    $particles[$generation + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$i]['ucp'] = $UCP;
                    $particles[$generation + 1][$i]['ae'] = abs($estimated_effort - floatval($projects['actualEffort']));
                    $particles[$generation + 1][$i]['xSimple'] = $positions['xSimple'];
                    $particles[$generation + 1][$i]['xAverage'] = $positions['xAverage'];
                    $particles[$generation + 1][$i]['xComplex'] = $positions['xComplex'];
                }
                $Xbest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
                $Xworst[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            } ## End if generation = 0

            if ($generation > 0) {
                // $chaotic[$generation + 1] = $this->singer($chaotic[$generation]);
                $r1[$generation + 1] = $chaos->chaotic($r1[$generation]);
                $r2[$generation + 1] = $chaos->chaotic($r2[$generation]);

                ## Entering Rao algorithm for HQ population
                foreach ($particles[$generation] as $i => $individu) {

                    //$candidates = $this->candidating($particles[$generation], $individu);

                    ## Rao-1 
                    // $xSimple = $individu['xSimple'] + $r1 * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']);
                    // $xAverage = $individu['xAverage'] + $r1 * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']);
                    // $xComplex = $individu['xComplex'] + $r1 * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']);

                    ## Rao-1 Chaotic
                    $xSimple = $particles[$generation][$i]['xSimple'] + $r1[$generation] * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']);
                    $xAverage = $particles[$generation][$i]['xAverage'] + $r1[$generation] * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']);
                    $xComplex = $particles[$generation][$i]['xComplex'] + $r1[$generation] * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']);

                    ## Rao-2
                    // $xSimple = $individu['xSimple'] + $r1 * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']) + ($r2 * (abs($candidates['xSimple1']) - abs($candidates['xSimple2'])));
                    // $xAverage = $individu['xAverage'] + $r1 * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']) + ($r2 * (abs($candidates['xAverage1']) - abs($candidates['xAverage2'])));
                    // $xComplex = $individu['xComplex'] + $r1 * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']) + ($r2 * (abs($candidates['xComplex1']) - abs($candidates['xComplex2'])));

                    ## Rao-3 
                    // $xSimple = $individu['xSimple'] + $r1[$generation]  * ($Xbest[$generation]['xSimple'] - abs($Xworst[$generation]['xSimple'])) + ($r2[$generation]  * (abs($candidates['xSimple1']) - $candidates['xSimple2']));
                    // $xAverage = $individu['xAverage'] + $r1[$generation]  * ($Xbest[$generation]['xAverage'] - abs($Xworst[$generation]['xAverage'])) + ($r2[$generation]  * (abs($candidates['xAverage1']) - $candidates['xAverage2']));
                    // $xComplex = $individu['xComplex'] + $r1[$generation]  * ($Xbest[$generation]['xComplex'] - abs($Xworst[$generation]['xComplex'])) + ($r2[$generation]  * (abs($candidates['xComplex1']) - $candidates['xComplex2']));


                    if ($xSimple < $this->range_positions['min_xSimple']) {
                        $xSimple = $this->range_positions['min_xSimple'];
                    }
                    if ($xSimple > $this->range_positions['max_xSimple']) {
                        $xSimple = $this->range_positions['max_xSimple'];
                    }
                    if ($xAverage < $this->range_positions['min_xAverage']) {
                        $xAverage = $this->range_positions['min_xAverage'];
                    }
                    if ($xAverage > $this->range_positions['max_xAverage']) {
                        $xAverage = $this->range_positions['max_xAverage'];
                    }
                    if ($xComplex < $this->range_positions['min_xComplex']) {
                        $xComplex = $this->range_positions['min_xComplex'];
                    }
                    if ($xComplex > $this->range_positions['max_xComplex']) {
                        $xComplex = $this->range_positions['max_xComplex'];
                    }

                    $positions = ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];

                    $UCP = $this->size($positions, $projects);
                    $esimated_effort = $UCP * $this->productivity_factor;
                    $particles[$generation + 1][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$generation + 1][$i]['ucp'] = $UCP;
                    $particles[$generation + 1][$i]['ae'] = abs($esimated_effort - floatval($projects['actualEffort']));
                    $particles[$generation + 1][$i]['xSimple'] = $positions['xSimple'];
                    $particles[$generation + 1][$i]['xAverage'] = $positions['xAverage'];
                    $particles[$generation + 1][$i]['xComplex'] = $positions['xComplex'];
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
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->UCP($project);
                }
                $positions['xSimple'] = array_sum(array_column($results, 'xSimple')) / $this->parameters['trials'];
                $positions['xAverage'] = array_sum(array_column($results, 'xAverage')) / $this->parameters['trials'];
                $positions['xComplex'] = array_sum(array_column($results, 'xComplex')) / $this->parameters['trials'];

                $UCP = $this->size($positions, $project);

                $estimated_effort = $UCP * $this->productivity_factor;
                $ae = abs($estimated_effort - floatval($project['actualEffort']));
                $ret[] = array('actualEffort' => $project['actualEffort'], 'estimatedEffort' => $estimated_effort, 'ucp' => $UCP, 'ae' => $ae, 'xSimple' => $positions['xSimple'], 'xAverage' => $positions['xAverage'], 'xComplex' => $positions['xComplex']);

                $results = [];
            }
        }
        return $ret;
    }
} ## End of Raoptimizer


$file_name = '../datasets/ucp_silhavy_dataset.txt';
$particle_size = 60;
$maximum_generation = 40;
$trials = 1000;
$s = 0.5;
$a = 0.5;
$b = 0.9;
$fitness = 10;

$parameters = ['particle_size' => $particle_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 's' => $s, 'a' => $a, 'b' => $b, 'fitness' => $fitness];
$productivity_factor = 20;

$range_positions = ['min_xSimple' => 5, 'max_xSimple' => 7.49, 'min_xAverage' => 7.5, 'max_xAverage' => 12.49, 'min_xComplex' => 12.5, 'max_xComplex' => 15];

$optimize = new Raoptimizer($file_name, $parameters, $productivity_factor, $range_positions);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 71;

echo '<p>';
foreach ($optimized as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<br>';
    $data = array($result['ae']);
    $fp = fopen('results/hasil_rao_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
