<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage;

use Doctrine\ORM\EntityManager;

class CheckoutStateDiffStorage implements CheckoutStateDiffStorageInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function addState($entity, array $data)
    {
        return uniqid('', false);
    }

    /**
     * {@inheritdoc}
     */
    public function readState($entity, $hash)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteStates($entity)
    {
    }
}
