<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class AssignTypePlaceholder extends AbstractPlaceholder
{
    const NAME = 'ASSIGN_TYPE';

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        return null;
    }
}
