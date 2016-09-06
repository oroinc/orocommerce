<?php

namespace Oro\Bundle\AccountBundle\Async\Visibility;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class VisibilityEntityProcessor extends AbstractVisibilityProcessor
{
    /**
     * @param object|VisibilityInterface $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        $this->cacheBuilder->resolveVisibilitySettings($entity);
    }
}
