<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Manager;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffManager
{
    /**
     * @var CheckoutStateDiffMapperRegistry
     */
    protected $mapperRegistry;

    /**
     * @param CheckoutStateDiffMapperRegistry $mapperRegistry
     */
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

        /** @var CheckoutStateDiffMapperInterface $mapper */
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
     * @param array $state1
     * @param array $state2
     * @return bool
     */
    public function isStatesEqual($entity, array $state1, array $state2)
    {
        /** @var CheckoutStateDiffMapperInterface $mapper */
        foreach ($this->mapperRegistry->getMappers() as $mapper) {
            if (!$mapper->isEntitySupported($entity)) {
                continue;
            }

            $name = $mapper->getName();

            if (!isset($state1[$name], $state2[$name])) {
                continue;
            }

            if (!$mapper->isStatesEqual($entity, $state1[$name], $state2[$name])) {
                return false;
            }
        }

        return true;
    }
}
