<?php

interface ChaoticInterface
{
    /**
     * Adds Chaotic Algorithms
     *
     * @param mixed  $chaos_value    Name of chaotic value getting from calculation or initialization
     *
     */
    public function chaotic(
        $chaos_value
    );
}

class BernoulliChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        if ($chaos_value > 0 && $chaos_value <= (1 - (1 / 2))) {
            return $chaos_value / (1 - (1 / 2));
        }
        if ($chaos_value > (1 - (1 / 2)) && $chaos_value < 1) {
            return ($chaos_value - (1 - (1 / 2))) / (1 / 2);
        }
    }
}

class ChebyshevChaotic implements ChaoticInterface
{
    protected $iteration;
    function __construct($iteration)
    {
        $this->iteration = $iteration;
    }

    public function chaotic($chaos_value)
    {
        return cos($this->iteration * cos(pow($chaos_value, -1)));
    }
}

class CircleChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        return fmod($chaos_value + 0.2 - (0.5 / (2 * pi())) * sin(2 * pi() * $chaos_value), 1);
    }
}

class GaussChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        if ($chaos_value == 0){
            $chaos_value = 0.00000001;
        }
        return fmod(1 / $chaos_value, 1);
    }
}

class LogisticChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        return (4 * $chaos_value) * (1 - $chaos_value);
    }
}

class SineChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        return sin(pi() * $chaos_value);
    }
}

class SingerChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        return 1.07 * ((7.86 * $chaos_value) - (23.31 * POW($chaos_value, 2)) + (28.75 * POW($chaos_value, 3)) - (13.302875 * POW($chaos_value, 4)));
    }
}

class SinuChaotic implements ChaoticInterface
{
    public function chaotic($chaos_value)
    {
        return (2.3 * POW($chaos_value, 2)) * sin(pi() * $chaos_value);
    }
}

/**
 * Chaotic algorithm selection
 *
 * @param string  $type       Type of chaotic algorithm choosen
 * @param mixed   $iteration  Number of current iteration 
 *
 */
class ChaoticFactory
{
    public function initializeChaotic($type, $iteration)
    {
        if ($type === 'bernoulli') {
            return new BernoulliChaotic();
        }
        if ($type === 'chebyshev') {
            return new ChebyshevChaotic($iteration);
        }
        if ($type === 'circle') {
            return new CircleChaotic();
        }
        if ($type === 'gauss') {
            return new GaussChaotic();
        }
        if ($type === 'logistic') {
            return new LogisticChaotic();
        }
        if ($type === 'sine') {
            return new SineChaotic();
        }
        if ($type === 'singer') {
            return new SingerChaotic();
        }
        if ($type === 'sinu') {
            return new SinuChaotic();
        }
    }
}

## Instantiation / usage
// $type = ['bernoulli', 'sine', 'chebyshev', 'circle', 'gauss', 'logistic', 'singer', 'sinu'];
// $chaos_value = 0.8;
// $iteration = 1;

// foreach ($type as $x) {
//     $chaoticFactory = new ChaoticFactory();
//     $chaos = $chaoticFactory->initializeChaotic($x, $iteration);
//     echo $chaos->chaotic($chaos_value);
//     echo '<br>';
// }
