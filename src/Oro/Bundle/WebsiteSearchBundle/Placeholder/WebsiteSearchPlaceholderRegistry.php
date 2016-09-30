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
     * @param string $placeholderName
     * @return WebsiteSearchPlaceholderInterface
     */
    public function getPlaceholder($placeholderName)
    {
        if (!isset($this->placeholders[$placeholderName])) {
            throw new \InvalidArgumentException(sprintf('Placeholder "%s" does not exist.', $placeholderName));
        }

        return $this->placeholders[$placeholderName];
    }

    /**
     * @return WebsiteSearchPlaceholderInterface[]
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }
}
