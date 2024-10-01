<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class EnumIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'ENUM_ID';

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        return null;
    }
}
