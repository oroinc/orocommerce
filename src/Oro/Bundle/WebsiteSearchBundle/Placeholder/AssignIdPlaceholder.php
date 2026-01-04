<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class AssignIdPlaceholder extends AbstractPlaceholder
{
    public const NAME = 'ASSIGN_ID';

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
