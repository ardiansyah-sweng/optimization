<?php
require_once '../optimizers/pso.php';
include '../estimator_interface.php';

/**
 * Use Case Points Software Effort Estimation
 * 
 * @input 
 */

class UseCasePointsEstimator implements EstimatorInterface
{
    private $pf = 20;

    public function estimator($complexity)
    {
        print_r($complexity);
        dd($complexity);
        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $parameters['uaw'] + $UUCW;
        $UCP = $UUCP * $parameters['tcf'] * $parameters['ecf'];

        return $UCP * $this->productivity_factor;
    }
}
