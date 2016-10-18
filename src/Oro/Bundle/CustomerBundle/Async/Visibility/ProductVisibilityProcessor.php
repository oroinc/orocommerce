<?php

namespace Oro\Bundle\CustomerBundle\Async\Visibility;

use Oro\Bundle\CustomerBundle\Entity\Visibility\VisibilityInterface;

class ProductVisibilityProcessor extends AbstractVisibilityProcessor
{
    /**
     * @param object|VisibilityInterface $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        $this->cacheBuilder->resolveVisibilitySettings($entity);
    }
}
