<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

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
