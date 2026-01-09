<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Central registry for all website search placeholder implementations.
 *
 * This registry stores and provides access to all registered {@see PlaceholderInterface} implementations
 * that are used throughout the website search system. Placeholders are registered during dependency
 * injection container compilation by {@see WebsiteSearchCompilerPass}. The registry allows retrieval
 * of specific placeholders by name and provides access to all registered placeholders for bulk operations
 * such as replacing all placeholders in a string or expression.
 */
class PlaceholderRegistry
{
    /**
     * @var array
     */
    private $placeholders = [];

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
