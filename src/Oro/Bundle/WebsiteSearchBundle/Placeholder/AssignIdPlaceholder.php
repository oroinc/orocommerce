<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class AssignIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'ASSIGN_ID';

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
