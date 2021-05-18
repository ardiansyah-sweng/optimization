<?php

class DataPreparation
{
    /**
     * dataProcessing
     *
     * @param  mixed $parameters
     * @return array
     */
    function dataProcessing($parameters)
    {
        if (!is_array($parameters)) {
            return new Exception("Parameters must be an array!");
        }
        $raw_data = file($parameters['file_name']);
        foreach ($raw_data as $val) {
            $data[] = explode(",", $val);
        }

        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $parameters['indexes'][$subkey]) {
                    $data[$key][$parameters['columns'][$subkey]] = $data[$key][$subkey];
                    unset($data[$key][$subkey]);
                }
            }
        }
        return [
            'dataset' => $data,
            'size' => count($data)
        ];
    }
}

class Population extends DataPreparation
{
    /**
     * generatePopulation
     *
     * @param  mixed $parameters
     * @return array
     */
    function generatePopulation($parameters)
    {
        $items = $this->dataProcessing($parameters);
        for ($i = 0; $i <= $parameters['population_size'] - 1; $i++) {
            for ($j = 0; $j <= $items['size'] - 1; $j++) {
                $ret[$i][$j] = rand(0, 1);
            }
        }
        return $ret;
    }
}

class Knapsack extends DataPreparation
{
    function pairingSolutionsAndItems($parameters, $optimized_solution)
    {
        $items = $this->dataProcessing($parameters);
        foreach ($optimized_solution as $key => $solution) {
            if ($solution === 1) {
                $ret[] = $items['dataset'][$key];
            }
        }
        return $ret;
    }

    function objective($parameters, $solutions)
    {
        $items = $this->dataProcessing($parameters);
        foreach ($solutions as $solution) {
            $selected_items = [];
            foreach ($items['dataset'] as $key => $item) {
                echo $solution[$key];
                echo ' ';
                print_r($item);
                echo '<br>';
                if ($solution[$key] === 1) {
                    $selected_items[] = $item['price'];
                }
            }
            $items_sum = array_sum($selected_items);
            echo $items_sum;
            echo '<p>';
            $ret[] = $items_sum;
        }
        return $ret;
    }

    function calculateFitness($deviations){
        foreach ($deviations as $deviation){
            if ($deviation['deviation'] < 0){
                $negative[] = $deviation['deviation'];
            }
            if ($deviation['deviation'] >= 0){
                $positive[] = $deviation['deviation'];
            }
        }

        if (empty($negative)){
            echo 'empty negative '.count($positive);
        }
        if (empty($positive)){
            echo 'empty positive '.count($negative);
        }
    }

    function fitnessEvaluation($parameters, $objectives)
    {
        if ($parameters['knapsack'] < 50000) {
            return "Your knapsack is " . $parameters['knapsack'] . ". Minimum knapsack is 50000!";
        }

        ## TODO if all deviations are negative
        foreach ($objectives as $key => $objective) {
            $deviation = $parameters['knapsack'] - $objective;
            echo $key . ' ' . $deviation;
            echo '<br>';
            $ret[] = [
                'index' => $key,
                'deviation' => $deviation
            ];
        }
        $this->calculateFitness($ret);
        $max = max(array_column($ret, 'deviation'));
        $index = array_search($max, array_column($ret, 'deviation'));
        return $ret[$index];
    }

    function optimization($parameters)
    {
        $populations = new Population;
        $solutions = $populations->generatePopulation($parameters);

        for ($generation = 0; $generation <= $parameters['max_generation']; $generation++) {
            if ($generation === 0) {
                $objectives = $this->objective($parameters, $solutions);
                $optimized = $this->fitnessEvaluation($parameters, $objectives);
                print_r($solutions[$optimized['index']]);
                echo '<br>';
                print_r($optimized);
                echo '<br>';
                $selected = $this->pairingSolutionsAndItems($parameters, $solutions[$optimized['index']]);
                print_r($selected);
                echo '<br>';
                echo $parameters['knapsack'] . ' ' . ($parameters['knapsack'] - $optimized['deviation']) . ' ' . $optimized['deviation'] . ' ' . count($selected) . ' item';
            }

            ## Fitness evaluation
            if ($optimized['deviation'] < $parameters['fitness']) {
                return $optimized;
            } else {
                $ret[] = $optimized;
            }
        }

        return $solutions;
    }
}

$parameters = [
    'file_name' => '../datasets/products.txt',
    'indexes' => [0, 1],
    'columns' => ['item', 'price'],
    'population_size' => 10,
    'fitness' => 1000,
    'max_generation' => 40,
    'knapsack' => 75000
];

$solution = new Knapsack;
$solution->optimization($parameters);
