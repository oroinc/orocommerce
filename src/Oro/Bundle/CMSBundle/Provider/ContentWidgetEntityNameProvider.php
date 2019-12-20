<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Represents ContentWidget entities by 'name' field avoiding usage of FullNameInterface.
 */
class ContentWidgetEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && $entity instanceof ContentWidget) {
            return $entity->getName();
        }

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
