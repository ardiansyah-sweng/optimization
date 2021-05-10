<?php
interface EstimatorInterface
{
    public function estimating($weights, $dataset);
}

class UseCasePointsEstimator implements EstimatorInterface
{
    protected $pf = 20;

    public function estimating($weights, $dataset)
    {
        foreach ($weights as $weight){
            $ucSimple = $weight[0] * $dataset['simpleUC'];
            $ucAverage = $weight[1] * $dataset['averageUC'];
            $ucComplex = $weight[2] * $dataset['complexUC'];
    
            $UUCW = $ucSimple + $ucAverage + $ucComplex;
            $UUCP = $dataset['uaw'] + $UUCW;
            $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];
            $estimated_effort = $UCP * $this->pf;
            $ae = abs($estimated_effort - floatval($dataset['actualEffort']));
            $ret[] = [
                'estimated'=>$estimated_effort, 
                'ae'=>$ae,
                'actualEffort'=>$dataset['actualEffort']
            ];
        }
        return $ret;
    }
}

class EstimatorFactory
{
    public function initializeOptimizer($type)
    {
        $types = [
            ['type' => 'ucp', 'estimator' => new UseCasePointsEstimator]
        ];
        $index = array_search($type, array_column($types, 'type'));
        return $types[$index]['estimator'];
    }
}
