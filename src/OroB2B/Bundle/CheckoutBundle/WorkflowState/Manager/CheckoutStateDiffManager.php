<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;

class CheckoutStateDiffManager
{
    /**
     * @var CheckoutStateDiffMapperInterface[]
     */
    protected $mappers;

    /**
     * @param CheckoutStateDiffMapperInterface $mapper
     */
    public function addMapper(CheckoutStateDiffMapperInterface $mapper)
    {
        $this->mappers[] = $mapper;
    }

    protected function getMappers()
    {
        usort(
            $this->mappers,
            function (CheckoutStateDiffMapperInterface $mapper1, CheckoutStateDiffMapperInterface $mapper2) {
                $priority1 = $mapper1->getPriority();
                $priority2 = $mapper2->getPriority();
                if ($priority1 == $priority2) {
                    return 0;
                }
                return ($priority1 < $priority2) ? -1 : 1;
            }
        );

        return $this->mappers;
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getCurrentState($entity)
    {
        $currentState = [];
        foreach ($this->getMappers() as $mapper) {
            if (!$mapper->isEntitySupported($entity)) {
                continue;
            }
            $currentState = array_merge($currentState, $mapper->getCurrentState($entity));
        }

        return $currentState;
    }

    /**
     * @param object $entity
     * @param array $savedState
     * @return bool
     */
    public function compareStates($entity, array $savedState)
    {
        foreach ($this->getMappers() as $mapper) {
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
