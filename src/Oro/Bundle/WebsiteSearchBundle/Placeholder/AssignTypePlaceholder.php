<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class AssignTypePlaceholder extends AbstractPlaceholder
{
    const NAME = 'ASSIGN_TYPE';

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * @return null
     */
    #[\Override]
    public function getDefaultValue()
    {
        return null;
    }
}
