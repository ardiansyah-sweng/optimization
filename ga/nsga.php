<?php

$data = [
    ['A', 2, 7.5],
    ['B', 3, 6],
    ['C', 3, 7.5],
    ['D', 4, 5],
    ['E', 4, 6.5],
    ['F', 5, 4.5],
    ['G', 5, 6],
    ['H', 5, 7],
    ['I', 6, 6.5]
];
$results = [];

function dominate($val11, $val12, $val21, $val22)
{
    if ($val11 < $val21 && $val12 < $val22) {
        return 'W';
    }
    if ($val11 > $val21 && $val12 > $val22) {
        return 'L';
    }
    if ($val11 < $val21 && $val12 === $val22) {
        return 'W';
    }
    if ($val11 > $val21 && $val12 === $val22) {
        return 'L';
    }
    if ($val11 === $val21 && $val12 < $val22) {
        return 'W';
    }
    if ($val11 === $val21 && $val12 > $val22) {
        return 'L';
    }
    return 'D';
}

function isEmpty($results)
{
    if (!$results) {
        return true;
    }
}

$res = [];
$counter = 0;
$generation = 0;
$temp = [];
while ($counter < count($data)) {
    echo ' Generation: '.$generation.'<p>';
    echo ' ===================== <br>';
    for ($i = 0; $i <= count($data) - 1; $i++) {
        echo 'Data ke: ' . $i;
        echo '<br> ====================== <br>';
        for ($j = 0; $j <= count($data) - 1; $j++) {
            if (array_diff($data[$i], $data[$j])) {
                print_r($data[$i]);
                echo ' vs ';
                print_r($data[$j]);
                echo '<br>';
                $hasil = dominate($data[$i][1], $data[$i][2], $data[$j][1], $data[$j][2]);
                if ($hasil === 'L') {
                    break;
                }
                if ($hasil === 'D' || $hasil === 'W') {
                    if (isEmpty($results)) {
                        $results[] = $data[$i];
                        break;
                    } else {
                        foreach ($results as $result) {
                            $hasil = dominate($data[$i][1], $data[$i][2], $result[1], $result[2]);

                            echo ' Hasil: ' . $hasil;
                            echo ' vs pareto front:<br>';
                            print_r($data[$i]);
                            echo ' === ';
                            print_r($result);
                            echo '<br>';

                            $res[] = $hasil;
                        }
                        echo ' hasil: ';
                        print_r($res);
                        echo '<p>';

                        if (!array_search('L', $res)) {
                            $results[] = $data[$i];
                            break;
                        }
                        $res = [];
                    }
                }
            }
        }
        echo '<p>';
        echo ' pareto front: <br>';
        print_r($results);
        echo '<p>';
    }

    foreach ($data as $key => $val) {
        $index = array_search($val[0], array_column($results, 0));
        if (!$index && $index !== 0) {
            $temp[] = $val;
        }
    }
    $data = $temp;
    $temp = [];
    $generation++;
}
