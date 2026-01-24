<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Storage;

/**
 * Defines the contract for storing and retrieving checkout state differences.
 *
 * Specifies methods for persisting checkout state snapshots and retrieving them for
 * comparison and change detection.
 */
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
