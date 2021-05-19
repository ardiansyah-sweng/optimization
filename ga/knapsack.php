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

    function selection($sorted_particles)
    {
        print_r($sorted_particles);
        echo '<p>';
        $parents = 2;
        foreach ($sorted_particles as $key => $val) {
            if ($key <= $parents - 1) {
                $ret[] = $val;
            }
        }
        return $ret;
    }

    function isOne($positive)
    {
        if (count($positive) === 1) {
            return true;
        }
    }

    function calculateFitness($deviations)
    {
        foreach ($deviations as $key => $deviation) {
            if ($deviation['deviation'] < 0) {
                $negative[] = [
                    'index' => $key,
                    'deviation' => $deviation['deviation']
                ];
            }
            if ($deviation['deviation'] >= 0) {
                $positive[] = [
                    'index' => $key,
                    'deviation' => $deviation['deviation']
                ];
            }
        }

        if (empty($negative)) {
            echo ' All positive <br>';
            print_r($positive);
            array_multisort(array_column($positive, 'deviation'), SORT_ASC, $positive);
            echo '<p>';
            $parents = $this->selection($positive);
            print_r($parents);
            return $parents;
        }
        if (empty($positive)) {
            echo ' All negative <br>';
            print_r($negative);
            array_multisort(array_column($negative, 'deviation'), SORT_DESC, $negative);
            echo '<p>';
            $parents = $this->selection($negative);
            print_r($parents);
            return $parents;
        }
        if ($this->isOne($positive)) {
            array_multisort(array_column($positive, 'deviation'), SORT_ASC, $positive);
            array_multisort(array_column($negative, 'deviation'), SORT_DESC, $negative);
            echo 'take negative';
            echo '<br>';
            print_r($positive[0]);
            echo '<br>';
            print_r($negative[0]);
            return [
                $positive[0], $negative[0]
            ];
        }
        if (!$this->isOne($positive)) {
            array_multisort(array_column($positive, 'deviation'), SORT_ASC, $positive);
            echo ' Positive > 1 <br>';
            $parents = $this->selection($positive);
            print_r($parents);
            return $parents;
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
        return $this->calculateFitness($ret);
    }

    function optimization($parameters)
    {
        $populations = new Population;
        $solutions = $populations->generatePopulation($parameters);

        for ($generation = 0; $generation <= $parameters['max_generation']; $generation++) {
            if ($generation === 0) {
                $objectives = $this->objective($parameters, $solutions);
                $optimized = $this->fitnessEvaluation($parameters, $objectives);
                echo '<p>';
                print_r($solutions[$optimized[0]['index']]);
                echo '<br>';
                print_r($solutions[$optimized[1]['index']]);
                echo '<p>';
                print_r($optimized);
                echo '<br>';
                $selected1 = $this->pairingSolutionsAndItems($parameters, $solutions[$optimized[0]['index']]);
                $selected2 = $this->pairingSolutionsAndItems($parameters, $solutions[$optimized[1]['index']]);
                echo '<p>';
                echo 'Your parcel 1:<br>';
                foreach ($selected1 as $item) {
                    echo $item['item'] . ' ' . $item['price'] . '<br>';
                }
                echo '<p>';
                echo 'Your parcel 2:<br>';
                foreach ($selected2 as $item) {
                    echo $item['item'] . ' ' . $item['price'] . '<br>';
                }
                echo '<p>';
                print_r($selected1);
                echo "<br>";
                print_r($selected2);
                echo '<br>';
                echo $parameters['knapsack'] . ' ' . ($parameters['knapsack'] - $optimized[0]['deviation']) . ' ' . $optimized[0]['deviation'] . ' ' . count($selected1) . ' '.count($selected2).' item';
            }

            ## Fitness evaluation
            if ($optimized[0]['deviation'] < $parameters['fitness']) {
                return $optimized;
            } else {
                $ret[] = $optimized[0];
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
    'knapsack' => 105000
];

$solution = new Knapsack;
$solution->optimization($parameters);
