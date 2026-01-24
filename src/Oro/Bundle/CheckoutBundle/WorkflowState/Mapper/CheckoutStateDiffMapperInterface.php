<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

/**
 * Defines the contract for mapping checkout state differences.
 *
 * Specifies methods for determining entity support, capturing current state, and comparing
 * state changes for checkout workflow state tracking.
 */
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
