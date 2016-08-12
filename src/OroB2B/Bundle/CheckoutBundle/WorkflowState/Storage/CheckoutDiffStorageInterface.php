<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

interface CheckoutDiffStorageInterface
{
    /**
     * @param object $entity
     * @param array $data
     * @param array $options
     * @return string
     */
    public function addState($entity, array $data, $options = []);

    /**
     * @param object $entity
     * @param string $token
     * @return array
     */
    public function getState($entity, $token);

    /**
     * @param object $entity
     * @param string|null $token
     */
    public function deleteStates($entity, $token = null);
}
