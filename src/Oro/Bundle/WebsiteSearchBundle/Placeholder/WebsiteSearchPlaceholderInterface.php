<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

interface WebsiteSearchPlaceholderInterface
{
    /**
     * Return placeholder name like 'WEBSITE_ID'
     *
     * @return string
     */
    public function getPlaceholder();

    /**
     * Return value for this placeholder
     *
     * @return string
     */
    public function getValue();
}
