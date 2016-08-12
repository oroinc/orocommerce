<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

trait IsStateEqualTrait
{
    /**
     * {@inheritdoc}
     */
    public function isStatesEqual($state1, $state2)
    {
        if (!isset($state1[$this->getName()], $state2[$this->getName()])) {
            return true;
        }

        return $state1[$this->getName()] === $state2[$this->getName()];
    }
}
