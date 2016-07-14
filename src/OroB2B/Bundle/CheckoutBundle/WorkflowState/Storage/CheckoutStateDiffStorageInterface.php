<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

interface CheckoutStateDiffStorageInterface
{
    /**
     * @param object $entity
     * @param array $data
     * @return $hash
     */
    public function addState($entity, array $data);

    /**
     * @param object $entity
     * @param string $hash
     * @return array
     */
    public function readState($entity, $hash);

    /**
     * @param object $entity
     * @return mixed
     */
    public function deleteStates($entity);
}