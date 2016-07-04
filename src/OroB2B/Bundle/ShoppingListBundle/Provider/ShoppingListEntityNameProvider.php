<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, ShoppingList::class)) {
            return $entity->getLabel();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === EntityNameProviderInterface::FULL && $className === self::class) {
            return sprintf('%s.label', $alias);
        }

        return false;
    }
}
