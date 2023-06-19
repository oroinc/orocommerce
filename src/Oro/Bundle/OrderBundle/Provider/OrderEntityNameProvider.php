<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Provides a text representation of Order entity.
 * Will be removed in the next version.
 */
class OrderEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }
}
