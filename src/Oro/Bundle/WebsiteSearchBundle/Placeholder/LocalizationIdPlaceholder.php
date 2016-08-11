<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class LocalizationIdPlaceholder implements WebsiteSearchPlaceholderInterface
{
    const NAME = 'LOCALIZATION_ID';

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
    public function getValue()
    {
        /**
         * TODO: Should be done in BB-7045
         */
        return '';
    }
}
