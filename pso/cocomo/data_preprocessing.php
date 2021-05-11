<?php

class DataPreprocessing
{
    protected $methods;
    protected $columns;

    function __construct($methods, $columns)
    {
        $this->methods = $methods;
        $this->columns = $columns;
    }

    /**
     * TODO NOte: when instantiate DataPreprocessing in different dir, getListFiles didnt return list of file. 
     */
    function getListFiles()
    {
        $ret = [];
        $fileList = glob('datasets/*.txt'); ## TODO be aware of static directory of filename
        foreach ($fileList as $filename) {
            if (is_file($filename)) {
                $ret[] = pathinfo($filename);
            }
        }
        return $ret;
    }

    /**
     * @return arrray method, base file_name
     * [basename] = filename with extention
     * [filename] = filename without extention
     */
    function getEstimationMethodName()
    {
        $ret = [];
        foreach ($this->getListFiles() as $dataset_file) {
            $method_names = explode("_", $dataset_file['filename']);
            $found = array_search($method_names[0], $this->methods);
            if ($found || $found === 0) {
                $ret[] = array(
                    'method' => $this->methods[$found],
                    'dataset_author' => $method_names[1],
                    'file_name' => $dataset_file['basename']
                );
            }
        }
        return $ret;
    }

    function isMatch($author, $method, $methods)
    {
        $data_author = array_search($author, array_column($methods, 'dataset_author'));
        $estimation_method = array_search($method, array_column($methods, 'method'));
        if ($data_author && $estimation_method || $data_author === 0 & $estimation_method === 0) {
            return true;
        }
    }

    function getFileNameIndex($dataset_files, $method)
    {
        return array_search($method, array_column($dataset_files, 'dataset_author'));
    }

    function prepareDataset()
    {
        $author = $this->columns['dataset_author'];
        $method = $this->columns['methods'];
        $dataset_files = $this->getEstimationMethodName();
        
        if (empty($dataset_files)){
            echo 'Empty files';
            exit();
        }

        if ($this->isMatch($author, $method, $dataset_files)) {
            $index_filename = $this->getFileNameIndex($dataset_files, $author);
            $raw_dataset = file('datasets/' . $this->getEstimationMethodName()[$index_filename]['file_name']); ## TODO be aware of static directory or filename
            foreach ($raw_dataset as $val) {
                $data[] = explode(",", $val);
            }

            foreach ($data as $key => $val) {
                foreach (array_keys($val) as $subkey) {
                    if ($subkey == $this->columns['column_index'][$subkey]) {
                        $data[$key][$this->columns['column_name'][$subkey]] = $data[$key][$subkey];
                        unset($data[$key][$subkey]);
                    }
                }
            }
            return [
                'data_size' => count($data),
                'projects' => $data
            ];
        }
        return new Exception("Author is not match!");
    }
}

## Instantiation / usage 
// $rawDataFactory = new RawDataFactory;
// $raw_data = $rawDataFactory->initializeRawData('silhavy');
// if (!$raw_data) {
//     echo 'Author does not exist';
//     exit();
// }
// $data = new DataPreprocessing($rawDataFactory->getEstimationMethods(), $raw_data->rawData());
// $dataset = $data->prepareDataset();
// print_r($dataset);
