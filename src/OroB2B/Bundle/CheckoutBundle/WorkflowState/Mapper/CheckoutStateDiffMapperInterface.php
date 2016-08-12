<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

interface CheckoutStateDiffMapperInterface
{
    /**
     * @param object $entity
     * @return bool
     */
    public function isEntitySupported($entity);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param object $entity
     * @return array
     */
    public function getCurrentState($entity);

    /**
     * @param object $entity
     * @param array $state1
     * @param array $state2
     * @return bool
     */
    public function isStatesEqual($entity, $state1, $state2);
}
