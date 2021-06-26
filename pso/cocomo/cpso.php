<?php
set_time_limit(1000000);
include 'raw_data_interface.php';
include 'data_preprocessing.php';
include 'chaotic_interface.php';
include 'seeds_class.php';

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
    private $trials;

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
        return floatval($A) * pow($size, $E) * array_product($effort_multipliers);
    }

    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
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

    function velocity($inertia, $R1, $R2, $velocity, $position, $Pbest, $Gbest)
    {
        return floatval($inertia) * floatval($velocity) + ($this->C1 * $R1) * (floatval($Pbest) - floatval($position)) + ($this->C2 * $R2) * (floatval($Gbest) - floatval($position));
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

    function findSolution($project, $initial_populations)
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

        $Gbests = [];
        for ($iteration = 0; $iteration <= $this->max_iteration - 1; $iteration++) {
            $inertia[$iteration + 1]  = $this->min_inertia - ((($this->max_inertia - $this->min_inertia) * $iteration) / $this->max_iteration);
            $chaoticFactory = new ChaoticFactory();
            $chaos1 = $chaoticFactory->initializeChaotic('singer', $iteration);
            $chaos2 = $chaoticFactory->initializeChaotic('sine', $iteration);

            ## Generate population
            if ($iteration === 0) {
                $R1[$iteration + 1] = $chaos1->chaotic($this->randomzeroToOne());
                $R2[$iteration + 1] = $chaos2->chaotic($this->randomzeroToOne());

                foreach ($initial_populations as $i => $initial_population) {
                    $B = $this->randomzeroToOne();
                    $E = $this->scaleEffortExponent($B, $SF); ## chaotic
                    $estimated_effort = $this->estimating($initial_population['A'], $project['kloc'], $E, $EM);
                    $particles[$iteration + 1][$i]['A'] = $initial_population['A'];
                    $particles[$iteration + 1][$i]['B'] = $B;
                    $particles[$iteration + 1][$i]['E'] = $E;
                    $particles[$iteration + 1][$i]['EM'] = array_sum($EM);
                    $particles[$iteration + 1][$i]['SF'] = array_sum($SF);
                    $particles[$iteration + 1][$i]['size'] = $project['kloc'];
                    $particles[$iteration + 1][$i]['effort'] = $project['effort'];
                    $particles[$iteration + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$iteration + 1][$i]['ae'] = abs($estimated_effort - $project['effort']);
                }
                $Pbests[$iteration + 1] = $particles[$iteration + 1];
                $Gbest[$iteration + 1] = $this->minimalAE($Pbests[$iteration + 1]);
            } ## End Generate Population

            if ($iteration > 0) {
                $R1[$iteration + 1] = $chaos1->chaotic($R1[$iteration]);
                $R2[$iteration + 1] = $chaos2->chaotic($R2[$iteration]);

                for ($i = 0; $i <= $this->swarm_size - 1; $i++) {
                    $GbestA = $Gbest[$iteration]['A'];
                    $PbestsA = $Pbests[$iteration][$i]['A'];
                    $velocity = $particles[$iteration][$i]['A'];
                    $A = $particles[$iteration][$i]['A'];

                    $velocity = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $velocity, $A, $PbestsA, $GbestA);
                    $A = floatval($A) + $velocity;
                    if ($A < $this->lower_bound) {
                        $A = $this->lower_bound;
                    }
                    if ($A > $this->upper_bound) {
                        $A = $this->upper_bound;
                    }

                    $E = $this->scaleEffortExponent($B, $SF); ## chaotic

                    $estimated_effort = $this->estimating($A, $project['kloc'], $E, $EM);

                    $particles[$iteration + 1][$i]['A'] = $A;
                    $particles[$iteration + 1][$i]['B'] = $B; ## chaotic
                    $particles[$iteration + 1][$i]['E'] = $E;
                    $particles[$iteration + 1][$i]['EM'] = array_sum($EM);
                    $particles[$iteration + 1][$i]['SF'] = array_sum($SF);
                    $particles[$iteration + 1][$i]['size'] = $project['kloc'];
                    $particles[$iteration + 1][$i]['effort'] = $project['effort'];
                    $particles[$iteration + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$iteration + 1][$i]['ae'] = abs($estimated_effort - $project['effort']);
                }
                $Pbests[$iteration + 1] = $this->comparePbests($Pbests[$iteration], $particles[$iteration + 1]);
                $Gbest[$iteration + 1] = $this->minimalAE($Pbests[$iteration + 1]);
                if ($Gbest[$iteration + 1] < $this->stopping_value) {
                    return $Gbest[$iteration + 1];
                }
                $Gbests[] = $Gbest[$iteration + 1];
            } ## End IF iteration > 0
        } ## End of iteration

        $minimal_AE = min(array_column($Gbests, 'ae'));
        $index_minimal_AE = array_search($minimal_AE, array_column($Gbests, 'ae'));
        return $Gbests[$index_minimal_AE];
    } ## End of findSolution()

    function finishing($numberOfRandomSeeds, $file_name)
    {
        $datasets = [
            'filename' => $file_name,
            'index' => 0,
            'name' => 'A'
        ];
        $initial_populations = new Read($datasets);
        $seeds = $initial_populations->datasetFile();
        $ret = [];

        for ($i = 0; $i <= $this->trials - 1; $i++) {
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
                    $results[] = $this->findSolution($projects, $initial_populations);
                }
            }
            $mae = Arithmatic::mae($results);
            $ret[] = $mae;
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
    'filenames/seeds_cpso_mucpso2.txt',
    'filenames/seeds_cpso_mucpso3.txt',
    'filenames/seeds_cpso_mucpso4.txt',
    'filenames/seeds_cpso_mucpso5.txt',
    'filenames/seeds_cpso_mucpso6.txt',
    'filenames/seeds_cpso_mucpso7.txt',
    'filenames/seeds_cpso_mucpso8.txt',
    'filenames/seeds_cpso_mucpso9.txt',
    'filenames/seeds_cpso_mucpso10.txt',
    'filenames/seeds_cpso_mucpso11.txt',
    'filenames/seeds_cpso_mucpso12.txt',
    'filenames/seeds_cpso_mucpso13.txt',
    'filenames/seeds_cpso_mucpso14.txt',
    'filenames/seeds_cpso_mucpso15.txt',
    'filenames/seeds_cpso_mucpso16.txt',
    'filenames/seeds_cpso_mucpso17.txt',
    'filenames/seeds_cpso_mucpso18.txt',
    'filenames/seeds_cpso_mucpso19.txt',
    'filenames/seeds_cpso_mucpso20.txt',
    'filenames/seeds_cpso_mucpso21.txt',
    'filenames/seeds_cpso_mucpso22.txt',
    'filenames/seeds_cpso_mucpso23.txt',
    'filenames/seeds_cpso_mucpso24.txt',
    'filenames/seeds_cpso_mucpso25.txt',
    'filenames/seeds_cpso_mucpso26.txt',
    'filenames/seeds_cpso_mucpso27.txt',
    'filenames/seeds_cpso_mucpso28.txt',
    'filenames/seeds_cpso_mucpso29.txt'
];

foreach ($fileNames as $file_name) {
    for ($numberOfRandomSeeds = 10; $numberOfRandomSeeds <= 2500; $numberOfRandomSeeds += 10) {
        $combinations = get_combinations(
            array(
                'particle_size' => array($numberOfRandomSeeds)
            )
        );
        
        foreach ($combinations as $key => $combination) {
            $dataset = 'cocomo_nasa93.txt';
            $swarm_size = $combination['particle_size'];
            $C1 = 1.5;
            $C2 = 1.5;
            $MAX_ITERATION = 40;
            $max_inertia = 0.9;
            $min_inertia = 0.4;
            $stopping_value = 10;
            $trials = 1;
            $productivity_factor = 20;
            $MAX_COUNTER = 100;

            $optimize = new ParticleSwarmOptimizer($swarm_size, $C1, $C2, $MAX_ITERATION, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $MAX_COUNTER, $trials, $scales);
            $optimized = $optimize->finishing($numberOfRandomSeeds, $file_name);
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

    $data = array($maxStagnantValue, $indexMaxStagnantValue);
    $fp = fopen('../results/tharwat.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
    $maes = [];
}
