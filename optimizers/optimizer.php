<?php

/**
 * Optimizer Algorithms
 *
 * created by Ardiansyah (ardiansyah@tif.uad.ac.id)
 * 
 * @param integer  $swarm_size      Size of swarm or population
 * @param mixed    $fitness         Stopping value
 * @param integer  $max_generation  Maximal generation or iteration allowed
 */

class Optimizer
{
    /**
     * @return mixed Array of particles dimension
     */
    function generatePopulation($parameters)
    {
        $ret = [];
        for ($i = 0; $i <= $parameters['swarm_size'] - 1; $i++) {
            foreach ($parameters['dimensions'] as $key => $dimension) {
                $ret[$i][$key] = mt_rand($dimension['lower'] * 100, $dimension['upper'] * 100) / 100;
            }
        }
        return $ret;
    }

    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    function minimalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(min($ae), $ae)];
    }

    function maximalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(max($ae), $ae)];
    }
}

