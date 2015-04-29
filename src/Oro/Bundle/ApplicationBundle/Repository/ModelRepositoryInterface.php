<?php

namespace Oro\Bundle\ApplicationBundle\Repository;

use Oro\Bundle\ApplicationBundle\Model\ModelInterface;

/**
 * Model repository used to manipulate models data and it is responsible for direct manipulations with data storage.
 * All model repositories have to implement this interface.
 */
interface ModelRepositoryInterface
{
    /**
     * Find model by its identifier and return found model or null
     *
     * @param mixed $modelIdentifier
     * @return ModelInterface|null
     */
    public function find($modelIdentifier);

    /**
     * Save model data to storage
     *
     * @param ModelInterface $model
     */
    public function save(ModelInterface $model);

    /**
     * Delete model data from storage
     *
     * @param ModelInterface $model
     */
    public function delete(ModelInterface $model);
}
