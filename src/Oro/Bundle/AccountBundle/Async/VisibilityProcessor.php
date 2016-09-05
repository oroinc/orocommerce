<?php

namespace Oro\Bundle\AccountBundle\Async;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class VisibilityProcessor extends AbstractVisibilityProcessor
{
    /**
     * @param object|VisibilityInterface $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        $this->cacheBuilder->resolveVisibilitySettings($entity);
    }
}
