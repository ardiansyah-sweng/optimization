<?php

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

class Arithmatic
{
    public static function mae($data)
    {
        return array_sum(array_column($data, 'ae')) / count($data);
    }
}