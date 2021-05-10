<?php

interface RawDataInterface
{
    public function rawData();
}

class SilhavyRawData implements RawDataInterface
{
    public function rawData()
    {
        return [
            'dataset_author' => 'silhavy',
            'methods' => 'ucp',
            'column_index' => [0, 1, 2, 3, 4, 5, 6],
            'column_name' => ['simpleUC', 'averageUC', 'complexUC', 'uaw', 'tcf', 'ecf', 'actualEffort']
        ];
    }
}

class ZiauddinRawData implements RawDataInterface
{
    public function rawData()
    {
        return [
            'dataset_author' => 'ziauddin',
            'methods' => 'agile',
            'column_index' => [0, 1, 2, 3, 4, 5, 6],
            'column_name' => ['effort', 'Vi', 'D', 'V', 'sprint_size', 'work_days', 'actual_time']
        ];
    }
}

class Nasa93RawData implements RawDataInterface
{
    public function rawData()
    {
        return  [
            'dataset_author' => 'nasa93',
            'methods' => 'cocomo',
            'column_index' => [],
            'column_name' => []
        ];
    }
}

/**
 * Chaotic algorithm selection
 *
 * @param string  $type       Type of chaotic algorithm choosen
 * @param mixed   $iteration  Number of current iteration 
 *
 */
class RawDataFactory
{
    protected $estimation_methods = [
        'cocomo', 'agile', 'ucp',
        'cosmic', 'analogy'
    ];

    function isFound($types, $type){
        $index = array_search($type, array_column($types, 'author'));
        if ($index || $index === 0){
            return $types[$index]['data'];
        }
    }

    public function initializeRawData($type)
    {
        $types = [
            ['author' => 'silhavy', 'data' => new SilhavyRawData()],
            ['author' => 'ziauddin', 'data' => new ZiauddinRawData()],
            ['author' => 'nasa93', 'data' => new Nasa93RawData()]
        ];
        if ($this->isFound($types, $type)) {
            return $this->isFound($types, $type); 
        }
        return false;
    }

    function getEstimationMethods()
    {
        return $this->estimation_methods;
    }
}

// ## Instantiation / usage {
// $rawDataFactory = new RawDataFactory();
// $raw_data = $rawDataFactory->initializeRawData('silhavy');
// print_r($raw_data->rawData());
// echo '<br>';
