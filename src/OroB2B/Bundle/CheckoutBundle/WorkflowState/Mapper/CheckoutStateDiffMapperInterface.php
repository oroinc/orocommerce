<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

interface CheckoutStateDiffMapperInterface
{
    /**
     * @return int
     */
    public function getMapperPriority();

    /**
     * @param object $entity
     * @return boolean
     */
    public function isEntitySupported($entity);

    /**
     * @param object $entity
     * @return array
     */
    public function getCurrentState($entity);

    /**
     * @param object $entity
     * @param array $savedState
     * @return bool
     */
    public function compareStates($entity, array $savedState);
}
