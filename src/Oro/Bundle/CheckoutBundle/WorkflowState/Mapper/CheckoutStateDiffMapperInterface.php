<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

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
     * @return mixed
     */
    public function getCurrentState($entity);

    /**
     * @param object $entity
     * @param mixed $state1
     * @param mixed $state2
     * @return bool
     */
    public function isStatesEqual($entity, $state1, $state2);
}
