<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class EnumIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'ENUM_ID';

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return null;
    }
}
