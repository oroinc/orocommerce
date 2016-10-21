<?php

namespace Oro\Bundle\WebCatalogBundle\PageProvider;

use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\WebCatalog\PageProviderInterface;

class WebCatalogPageProviderRegistry
{
    /**
     * @var PageProviderInterface[]
     */
    protected $pageProviders = [];
    
    /**
     * @param PageProviderInterface $pageProvider
     */
    public function addProvider(PageProviderInterface $pageProvider)
    {
        $this->pageProviders[$pageProvider->getName()] = $pageProvider;
    }

    /**
     * @param string $pageProviderName
     * @return PageProviderInterface
     * @throws InvalidArgumentException
     */
    public function getProvider($pageProviderName)
    {
        if (!array_key_exists($pageProviderName, $this->pageProviders)) {
            throw new InvalidArgumentException(sprintf('Page provider "%s" does not exist.', $pageProviderName));
        }

        return $this->pageProviders[$pageProviderName];
    }

    /**
     * @return PageProviderInterface[]
     */
    public function getProviders()
    {
        return $this->pageProviders;
    }
}
