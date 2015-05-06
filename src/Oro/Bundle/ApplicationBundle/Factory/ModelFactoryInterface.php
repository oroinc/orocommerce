<?php

namespace Oro\Bundle\ApplicationBundle\Factory;

use Oro\Bundle\ApplicationBundle\Model\ModelInterface;

/**
 * Model factory interface should be implemented by all model factories
 */
interface ModelFactoryInterface
{
    /**
     * Create model based on input arguments
     *
     * @param array $arguments
     * @return ModelInterface
     */
    public function create(array $arguments = []);
}
