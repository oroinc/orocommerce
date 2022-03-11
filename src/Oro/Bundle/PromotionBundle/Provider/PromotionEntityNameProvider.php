<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Provide title for Promotion entity
 */
class PromotionEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Promotion) {
            return false;
        }

        return $entity->getRule()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }
}
