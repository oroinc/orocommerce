<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class WebsiteSearchPlaceholderRegistry
{
    /**
     * @var array
     */
    private $placeholders = [];

    /**
     * @param WebsiteSearchPlaceholderInterface $placeholder
     * @return array
     */
    public function addPlaceholder(WebsiteSearchPlaceholderInterface $placeholder)
    {
        $this->placeholders[$placeholder->getPlaceholder()] = $placeholder;
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }
}
