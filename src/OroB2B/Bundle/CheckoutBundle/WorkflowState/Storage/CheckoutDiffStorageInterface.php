<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

interface CheckoutDiffStorageInterface
{
    /**
     * @param object $entity
     * @param array $data
     * @return $token
     */
    public function addState($entity, array $data);

    /**
     * @param object $entity
     * @param string $token
     * @return array
     */
    public function readState($entity, $token);

    /**
     * @param object $entity
     * @return mixed
     */
    public function deleteStates($entity);
}
