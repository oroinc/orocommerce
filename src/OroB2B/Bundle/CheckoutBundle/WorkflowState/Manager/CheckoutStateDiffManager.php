<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;

class CheckoutStateDiffManager
{
    /**
     * @var CheckoutStateDiffMapperInterface[]
     */
    protected $mappers = [];

    /**
     * @param CheckoutStateDiffMapperInterface $mapper
     */
    public function addMapper(CheckoutStateDiffMapperInterface $mapper)
    {
        $this->mappers[] = $mapper;
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getCurrentState($entity)
    {
        $currentState = [];
        foreach ($this->mappers as $mapper) {
            if (!$mapper->isEntitySupported($entity)) {
                continue;
            }
            $currentState[] = $mapper->getCurrentState($entity);
        }

        return call_user_func_array('array_merge', $currentState);
    }

    /**
     * @param object $entity
     * @param array $savedState
     * @return bool
     */
    public function compareStates($entity, array $savedState)
    {
        foreach ($this->mappers as $mapper) {
            if (!$mapper->isEntitySupported($entity)) {
                continue;
            }
            if (!$mapper->compareStates($entity, $savedState)) {
                return false;
            }
        }

        return true;
    }
}
