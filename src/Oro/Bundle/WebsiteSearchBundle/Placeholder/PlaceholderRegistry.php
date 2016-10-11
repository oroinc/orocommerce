<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class PlaceholderRegistry
{
    /**
     * @var array
     */
    private $placeholders = [];

    /**
     * @param PlaceholderInterface $placeholder
     */
    public function addPlaceholder(PlaceholderInterface $placeholder)
    {
        $this->placeholders[$placeholder->getPlaceholder()] = $placeholder;
    }

    /**
     * @param string $placeholderName
     * @return PlaceholderInterface
     */
    public function getPlaceholder($placeholderName)
    {
        if (!isset($this->placeholders[$placeholderName])) {
            throw new \InvalidArgumentException(sprintf('Placeholder "%s" does not exist.', $placeholderName));
        }

        return $this->placeholders[$placeholderName];
    }

    /**
     * @return PlaceholderInterface[]
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }
}
