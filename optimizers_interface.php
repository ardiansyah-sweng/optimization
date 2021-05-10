<?php
require_once 'optimizer.php';

interface OptimizersInterface
{
    /**
     * Adds Optimizer Algorithms
     *
     *
     */
    public function optimizer();
}

class Rao1Optimizer implements OptimizersInterface
{
    protected $parameters;

    function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    public function optimizer()
    {
        return $this->parameters['X'] + $this->parameters['r1'] * ($this->parameters['best_X'] - $this->parameters['worst_X']);
    }
}

class Rao2Optimizer implements OptimizersInterface
{
    protected $parameters;

    function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    function candidating($particles, $individu)
    {
        for ($i = 1; $i <= 2; $i++) {
            $ae_candidate = $particles[array_rand($particles)];

            if ($individu['ae'] > $ae_candidate['ae']) {
                $candidate = $ae_candidate['A'];
            }
            if ($individu['ae'] < $ae_candidate['ae'] || $individu['ae'] == $ae_candidate['ae']) {
                $candidate = $individu['A'];
            }
            $ret[$i] = $candidate;
        }
        return $ret;
    }

    public function optimizer()
    {
        $candidates = $this->candidating($this->parameters['particles'], $this->parameters['individu']);

        return $this->parameters['X'] + $this->parameters['r1'] * ($this->parameters['best_X'] - $this->parameters['worst_X']) + ($this->parameters['r2'] * (abs($candidates[1]) - abs($candidates[2])));
    }
}

class Rao3Optimizer extends Rao2Optimizer implements OptimizersInterface
{
    protected $parameters;

    function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    public function optimizer()
    {
        $candidates = $this->candidating($this->parameters['particles'], $this->parameters['individu']);

        return $this->parameters['X'] + $this->parameters['r1'] * ($this->parameters['best_X'] - abs($this->parameters['worst_X'])) + ($this->parameters['r2'] * (abs($candidates[1]) - $candidates[2]));
    }
}

/**
 * Chaotic algorithm selection
 *
 * @param string  $type       Type of chaotic algorithm choosen
 * @param mixed   $iteration  Number of current iteration 
 *
 */
class OptimizerFactory
{
    public function initializeOptimizer($type, $generation, $parameters)
    {
        $types = [
            ['type' => 'rao1', 'optimizer' => new Rao1Optimizer($parameters)],
            ['type' => 'rao2', 'optimizer' => new Rao2Optimizer($parameters)],
            ['type' => 'rao3', 'optimizer' => new Rao3Optimizer($parameters)]
        ];
        $index = array_search($type, array_column($types, 'type'));
        return $types[$index]['optimizer'];
    }
}
