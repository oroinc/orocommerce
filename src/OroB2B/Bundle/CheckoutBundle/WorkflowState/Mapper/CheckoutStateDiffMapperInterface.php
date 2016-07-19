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
     * @return mixed
     */
    public function getCurrentState($entity);

    /**
     * @param object $entity
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($entity, array $savedState);
}
