<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, Order::class)) {
            return sprintf('%s %s %s', $entity->getIdentifier(), $entity->getPoNumber(), $entity->getCurrency());
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === EntityNameProviderInterface::FULL && $className === Order::class) {
            return sprintf('%s.identifier', $alias);
        }

        return false;
    }
}
