<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffManager
{
    /**
     * @var CheckoutStateDiffMapperRegistry
     */
    private $mapperRegistry;

    public function __construct(CheckoutStateDiffMapperRegistry $mapperRegistry)
    {
        $this->mapperRegistry = $mapperRegistry;
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getCurrentState($entity)
    {
        $currentState = [];
        foreach ($this->mapperRegistry->getMappers() as $mapper) {
            if (!$mapper->isEntitySupported($entity)) {
                continue;
            }
            $currentState[$mapper->getName()] = $mapper->getCurrentState($entity);
        }

        return $currentState;
    }

    /**
     * @param object $entity
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($entity, array $savedState)
    {
        foreach ($this->mapperRegistry->getMappers() as $mapper) {
            if (!$mapper->isEntitySupported($entity)) {
                continue;
            }
            if (!$mapper->isStateActual($entity, $savedState)) {
                return false;
            }
        }

        return true;
    }
}
