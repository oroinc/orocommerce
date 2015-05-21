<?php

namespace Oro\Bundle\ApplicationBundle\Model;

/**
 * Abstract model provides most basic functions of regular model
 */
abstract class AbstractModel implements ModelInterface
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @param object $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        return [$this->entity];
    }
}
