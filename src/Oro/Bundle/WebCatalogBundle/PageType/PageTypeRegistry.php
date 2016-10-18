<?php

namespace Oro\Bundle\WebCatalogBundle\PageType;

use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\WebCatalog\PageTypeInterface;

class PageTypeRegistry
{
    /**
     * @var PageTypeInterface[]
     */
    private $pageTypes = [];

    /**
     * @param PageTypeInterface $pageType
     */
    public function addPageType(PageTypeInterface $pageType)
    {
        $this->pageTypes[$pageType->getName()] = $pageType;
    }

    /**
     * @param string $pageTypeName
     * @return PageTypeInterface
     */
    public function getPageType($pageTypeName)
    {
        if (!array_key_exists($pageTypeName, $this->pageTypes)) {
            throw new InvalidArgumentException(sprintf('Page type "%s" does not exist.', $pageTypeName));
        }

        return $this->pageTypes[$pageTypeName];
    }
    
    /**
     * @return PageTypeInterface[]
     */
    public function getPageTypes()
    {
        return $this->pageTypes;
    }
}
